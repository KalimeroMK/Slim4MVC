<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\RateLimitMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $middleware;

    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestFactory = new ServerRequestFactory();
    }

    public function test_allows_requests_within_limit(): void
    {
        $this->middleware = new RateLimitMiddleware(5, 60);
        $request = $this->createRequest('127.0.0.1');
        $handler = $this->createHandler();

        // Make 5 requests (within limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->middleware->process($request, $handler);
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function test_blocks_requests_exceeding_limit(): void
    {
        $this->middleware = new RateLimitMiddleware(3, 60);
        $request = $this->createRequest('127.0.0.1');
        $handler = $this->createHandler();

        // Make 3 requests (at limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->middleware->process($request, $handler);
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 4th request should be blocked
        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('Too Many Requests', (string) $response->getBody());
    }

    public function test_adds_rate_limit_headers(): void
    {
        $this->middleware = new RateLimitMiddleware(5, 60);
        $request = $this->createRequest('127.0.0.1');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('X-RateLimit-Limit'));
        $this->assertTrue($response->hasHeader('X-RateLimit-Remaining'));
        $this->assertEquals('5', $response->getHeaderLine('X-RateLimit-Limit'));
    }

    public function test_different_ips_have_separate_limits(): void
    {
        $this->middleware = new RateLimitMiddleware(2, 60);
        $handler = $this->createHandler();

        $request1 = $this->createRequest('127.0.0.1');
        $request2 = $this->createRequest('192.168.1.1');

        // IP 1 uses its limit
        $this->middleware->process($request1, $handler);
        $this->middleware->process($request1, $handler);

        // IP 2 should still be able to make requests
        $response = $this->middleware->process($request2, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function createRequest(string $ip): ServerRequestInterface
    {
        $serverParams = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/test',
            'REMOTE_ADDR' => $ip,
        ];
        $request = $this->requestFactory->createServerRequest('POST', '/test', $serverParams);

        return $request;
    }

    private function createHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturn(new Response(200));

        return $handler;
    }
}
