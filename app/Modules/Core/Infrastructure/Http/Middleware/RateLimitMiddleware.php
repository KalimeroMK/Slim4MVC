<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as Psr7Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private array $requests = [];

    public function __construct(
        private readonly int $maxRequests = 60,
        private readonly int $windowSeconds = 60
    ) {}

    public function process(Request $request, Handler $handler): Response
    {
        $identifier = $this->getIdentifier($request);
        $now = time();

        // Clean old entries
        $this->cleanOldEntries($now);

        // Check rate limit
        if ($this->isRateLimited($identifier, $now)) {
            $response = new Psr7Response();
            $response->getBody()->write(json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('Retry-After', (string) $this->windowSeconds)
                ->withStatus(429);
        }

        // Increment request count
        $this->incrementRequest($identifier, $now);

        $response = $handler->handle($request);

        // Add rate limit headers
        $remaining = max(0, $this->maxRequests - ($this->requests[$identifier]['count'] ?? 0));

        return $response->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }

    private function getIdentifier(Request $request): string
    {
        // Use IP address as identifier
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? 'unknown';

        // If behind proxy, check X-Forwarded-For
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if (! empty($forwardedFor)) {
            $ips = explode(',', $forwardedFor);
            $ip = mb_trim($ips[0]);
        }

        return $ip;
    }

    private function isRateLimited(string $identifier, int $now): bool
    {
        if (! isset($this->requests[$identifier])) {
            return false;
        }

        $requestData = $this->requests[$identifier];

        // Check if window has expired
        if ($now - $requestData['start'] >= $this->windowSeconds) {
            return false;
        }

        return $requestData['count'] >= $this->maxRequests;
    }

    private function incrementRequest(string $identifier, int $now): void
    {
        if (! isset($this->requests[$identifier])) {
            $this->requests[$identifier] = [
                'count' => 0,
                'start' => $now,
            ];
        }

        $requestData = $this->requests[$identifier];

        // Reset if window expired
        if ($now - $requestData['start'] >= $this->windowSeconds) {
            $this->requests[$identifier] = [
                'count' => 1,
                'start' => $now,
            ];
        } else {
            $this->requests[$identifier]['count']++;
        }
    }

    private function cleanOldEntries(int $now): void
    {
        foreach ($this->requests as $identifier => $data) {
            if ($now - $data['start'] >= $this->windowSeconds) {
                unset($this->requests[$identifier]);
            }
        }
    }
}
