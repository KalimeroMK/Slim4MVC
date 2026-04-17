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
 * Simple CORS implementation using PSR-17 factories.
 * Supports psr/http-message ^2.0
 */
class CorsMiddleware implements MiddlewareInterface
{
    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(private readonly ResponseFactoryInterface $responseFactory, array $options = [])
    {
        $this->options = array_merge([
            'origin' => ['*'],
            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'headers.allow' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'headers.expose' => [],
            'credentials' => false,
            'cache' => 86400,
        ], $options);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = $this->createResponse();

            return $this->addCorsHeaders($request, $response);
        }

        // Process the request
        $response = $handler->handle($request);

        // Add CORS headers to response
        return $this->addCorsHeaders($request, $response);
    }

    private function createResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse(200);
    }

    private function addCorsHeaders(ServerRequestInterface $serverRequest, ResponseInterface $response): ResponseInterface
    {
        $origin = $this->getOrigin($serverRequest);

        // Add Origin header
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        } elseif ($this->options['origin'] === ['*']) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Add Vary header
        $response = $response->withHeader('Vary', 'Origin');

        // Add credentials header
        if ($this->options['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        // Add allowed methods
        $response = $response->withHeader(
            'Access-Control-Allow-Methods',
            implode(', ', $this->options['methods'])
        );

        // Add allowed headers
        $response = $response->withHeader(
            'Access-Control-Allow-Headers',
            implode(', ', $this->options['headers.allow'])
        );

        // Add exposed headers
        if (! empty($this->options['headers.expose'])) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->options['headers.expose'])
            );
        }

        // Add cache header
        if ($this->options['cache'] > 0) {
            return $response->withHeader(
                'Access-Control-Max-Age',
                (string) $this->options['cache']
            );
        }

        return $response;
    }

    private function getOrigin(ServerRequestInterface $serverRequest): string
    {
        $headers = $serverRequest->getHeader('Origin');

        return $headers[0] ?? '';
    }

    private function isOriginAllowed(string $origin): bool
    {
        if ($this->options['origin'] === ['*']) {
            return true;
        }

        return in_array($origin, $this->options['origin'], true);
    }
}
