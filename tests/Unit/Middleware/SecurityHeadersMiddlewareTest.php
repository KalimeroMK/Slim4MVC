<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Modules\Core\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * @covers \App\Modules\Core\Infrastructure\Http\Middleware\SecurityHeadersMiddleware
 */
final class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;

    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
        $this->requestFactory = new ServerRequestFactory();
    }

    public function test_adds_security_headers(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertFalse(
            $response->hasHeader('X-XSS-Protection'),
            'The legacy XSS auditor header is obsolete and should not be sent.'
        );
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertSame('geolocation=(), microphone=(), camera=()', $response->getHeaderLine('Permissions-Policy'));
    }

    public function test_adds_csp_header(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('Content-Security-Policy'));
        $csp = $response->getHeaderLine('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_adds_hsts_in_production(): void
    {
        $_ENV['APP_ENV'] = 'production';

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('Strict-Transport-Security'));
        $this->assertStringContainsString('max-age=31536000', $response->getHeaderLine('Strict-Transport-Security'));
        $this->assertStringContainsString('includeSubDomains', $response->getHeaderLine('Strict-Transport-Security'));
    }

    public function test_does_not_add_hsts_in_development(): void
    {
        $_ENV['APP_ENV'] = 'development';

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertFalse($response->hasHeader('Strict-Transport-Security'));
    }

    public function test_leaves_a_policy_the_route_set_for_itself(): void
    {
        // A route that serves someone else's content has to be able to say so. The
        // default policy below suits pages this application renders; imposing it on a
        // response that already carries one - a page embedding third-party images, say -
        // silently breaks that page, and the route has no way to object.
        $own = "default-src 'none'; img-src *";
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(
            (new Response(200))->withHeader('Content-Security-Policy', $own)
        );

        $response = $this->middleware->process(
            $this->requestFactory->createServerRequest('GET', '/'),
            $handler
        );

        $this->assertSame($own, $response->getHeaderLine('Content-Security-Policy'));

        // The rest are still applied: only the policy is deferred to.
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    private function createHandler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200));

        return $handler;
    }
}
