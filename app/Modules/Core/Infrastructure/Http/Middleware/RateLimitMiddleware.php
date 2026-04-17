<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as Psr7Response;

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
        $identifier = $this->getIdentifier($request);
        $now = time();
        $cacheKey = 'rate_limit:'.$identifier;

        // Get current request count from cache
        $requestData = $this->cache->get($cacheKey, null);

        if (! is_array($requestData) || ! isset($requestData['count'], $requestData['start'])) {
            $requestData = ['count' => 0, 'start' => $now];
        }

        // Reset if window expired
        if ($now - $requestData['start'] >= $this->windowSeconds) {
            $requestData = ['count' => 0, 'start' => $now];
        }

        // Check rate limit
        if ($requestData['count'] >= $this->maxRequests) {
            $response = new Psr7Response();
            $json = json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
            ]);
            $response->getBody()->write($json !== false ? $json : '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('Retry-After', (string) $this->windowSeconds)
                ->withStatus(429);
        }

        // Increment request count
        $requestData['count']++;
        $this->cache->set($cacheKey, $requestData, $this->windowSeconds);

        $response = $handler->handle($request);

        $remaining = max(0, $this->maxRequests - $requestData['count']);

        return $response->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
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
