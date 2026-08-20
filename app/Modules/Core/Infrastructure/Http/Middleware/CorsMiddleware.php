<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS Middleware - PSR-15 compatible
 *
 * Security model:
 *  - An origin is echoed back only when it matches the configured allowlist exactly.
 *    An unknown origin gets no `Access-Control-Allow-Origin` header at all, so the
 *    browser blocks the response.
 *  - Wildcard (`*`) and credentials are mutually exclusive. Browsers reject
 *    `Access-Control-Allow-Origin: *` together with credentials, and the alternative
 *    (reflecting whatever origin asked) would let any site read authenticated
 *    responses. When both are configured, credentials are dropped.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private readonly array $allowedOrigins;

    private readonly bool $allowAllOrigins;

    private readonly bool $allowCredentials;

    private readonly bool $credentialsDowngraded;

    /** @var list<string> */
    private readonly array $methods;

    /** @var list<string> */
    private readonly array $allowHeaders;

    /** @var list<string> */
    private readonly array $exposeHeaders;

    private readonly int $maxAge;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(private readonly ResponseFactoryInterface $responseFactory, array $options = [])
    {
        $origins = self::normalizeList($options['origin'] ?? ['*']);

        $this->allowAllOrigins = in_array('*', $origins, true);
        $this->allowedOrigins = $this->allowAllOrigins
            ? []
            : array_values(array_filter($origins, static fn (string $o): bool => $o !== '*'));

        $credentialsRequested = (bool) ($options['credentials'] ?? false);
        $this->allowCredentials = $credentialsRequested && ! $this->allowAllOrigins;
        $this->credentialsDowngraded = $credentialsRequested && $this->allowAllOrigins;

        $this->methods = self::normalizeList(
            $options['methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
        );
        $this->allowHeaders = self::normalizeList(
            $options['headers.allow'] ?? ['Content-Type', 'Authorization', 'X-Requested-With']
        );
        $this->exposeHeaders = self::normalizeList($options['headers.expose'] ?? []);
        $this->maxAge = (int) ($options['cache'] ?? 86400);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $this->addCorsHeaders($request, $this->responseFactory->createResponse(200));
        }

        return $this->addCorsHeaders($request, $handler->handle($request));
    }

    /**
     * Whether credentials are actually advertised. False when the wildcard origin
     * forced them off, regardless of what was configured.
     */
    public function allowsCredentials(): bool
    {
        return $this->allowCredentials;
    }

    /**
     * True when `credentials` was requested but disabled because origin is `*`.
     * Useful for surfacing the misconfiguration at boot.
     */
    public function credentialsWereDowngraded(): bool
    {
        return $this->credentialsDowngraded;
    }

    /**
     * @return list<string>
     */
    private static function normalizeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item !== '') {
                $normalized[] = $item;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function addCorsHeaders(ServerRequestInterface $serverRequest, ResponseInterface $response): ResponseInterface
    {
        $origin = $this->getOrigin($serverRequest);

        // Origin header. Only ever `*` (no credentials) or an exact allowlist match.
        if ($this->allowAllOrigins) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        } elseif ($origin !== '' && in_array($origin, $this->allowedOrigins, true)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        // Vary on Origin so caches never serve one origin's response to another.
        $response = $response->withHeader('Vary', 'Origin');

        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        $response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', $this->methods));
        $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', $this->allowHeaders));

        if ($this->exposeHeaders !== []) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->exposeHeaders));
        }

        if ($this->maxAge > 0) {
            return $response->withHeader('Access-Control-Max-Age', (string) $this->maxAge);
        }

        return $response;
    }

    private function getOrigin(ServerRequestInterface $serverRequest): string
    {
        return $serverRequest->getHeaderLine('Origin');
    }
}
