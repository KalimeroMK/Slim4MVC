<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as Psr7Response;

/**
 * Fixed-window rate limiter.
 *
 * The window is encoded in the cache key, and the counter is advanced with a single
 * atomic increment. A read-then-write counter would let concurrent requests all
 * observe the same pre-limit value and pass together.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private readonly CacheInterface $cache;

    /** @var list<string> */
    private readonly array $trustedProxies;

    public function __construct(
        CacheInterface $cache,
        private readonly int $maxRequests = 60,
        private readonly int $windowSeconds = 60
    ) {
        $this->cache = $cache;
        $raw = $_ENV['TRUSTED_PROXIES'] ?? '';
        $this->trustedProxies = $raw !== ''
            ? array_map('trim', explode(',', $raw))
            : [];
    }

    public function process(Request $request, Handler $handler): Response
    {
        $now = time();
        $window = intdiv($now, $this->windowSeconds);
        $cacheKey = sprintf('rate_limit:%s:%d', $this->getIdentifier($request), $window);

        // Keep the counter a little past the window so a clock skew between the
        // increment and the expiry cannot drop it early.
        $count = $this->cache->increment($cacheKey, 1, $this->windowSeconds * 2);

        if ($count === false) {
            // The driver cannot count (no cache configured, or the key holds
            // something else). Fail open rather than locking everyone out.
            return $handler->handle($request);
        }

        $resetsAt = ($window + 1) * $this->windowSeconds;
        $remaining = max(0, $this->maxRequests - $count);

        if ($count > $this->maxRequests) {
            return $this->tooManyRequests(max(1, $resetsAt - $now), $resetsAt);
        }

        return $this->withRateLimitHeaders($handler->handle($request), $remaining, $resetsAt);
    }

    private function tooManyRequests(int $retryAfter, int $resetsAt): Response
    {
        $response = new Psr7Response();
        $json = json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
        ]);
        $response->getBody()->write($json !== false ? $json : '{}');

        return $this->withRateLimitHeaders($response, 0, $resetsAt)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withStatus(429);
    }

    private function withRateLimitHeaders(Response $response, int $remaining, int $resetsAt): Response
    {
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining)
            ->withHeader('X-RateLimit-Reset', (string) $resetsAt);
    }

    private function getIdentifier(Request $request): string
    {
        $serverParams = $request->getServerParams();
        $remoteAddr = (string) ($serverParams['REMOTE_ADDR'] ?? 'unknown');

        // Only trust X-Forwarded-For when the request comes from a known proxy
        if ($this->trustedProxies !== [] && in_array($remoteAddr, $this->trustedProxies, true)) {
            $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
            if ($forwardedFor !== '') {
                $ips = explode(',', $forwardedFor);
                $clientIp = mb_trim($ips[0]);
                if (filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
                    return $clientIp;
                }
            }
        }

        return $remoteAddr;
    }
}
