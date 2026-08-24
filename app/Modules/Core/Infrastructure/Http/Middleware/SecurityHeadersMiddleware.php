<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $response = $handler->handle($request);

        $response = $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            // X-XSS-Protection is deliberately absent: the legacy auditor it enabled is
            // gone from current browsers and could itself be abused. CSP covers this.
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Only when the response has not set one itself. The default below suits pages
        // this application renders; a route that serves someone else's content has to be
        // able to say so. The full-text feed is the case in hand: browsers render it
        // through an XSL stylesheet, and the articles embed images from whatever site
        // they came from, which `img-src 'self'` blocks outright - every picture in the
        // feed came out broken while the route's own, deliberately narrower policy was
        // being overwritten here.
        if (! $response->hasHeader('Content-Security-Policy')) {
            $response = $response->withHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';"
            );
        }

        if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
