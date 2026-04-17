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
        $this->assertSame('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
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

    private function createHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200));

        return $handler;
    }
}
