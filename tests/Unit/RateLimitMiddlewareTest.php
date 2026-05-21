<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Http\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

final class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $rateLimitMiddleware;

    private ServerRequestFactory $serverRequestFactory;

    private Stub $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverRequestFactory = new ServerRequestFactory();
        $this->cache = $this->createStub(CacheInterface::class);
    }

    public function test_allows_requests_within_limit(): void
    {
        $this->rateLimitMiddleware = new RateLimitMiddleware($this->cache, 5, 60);
        $serverRequest = $this->createRequest('127.0.0.1');
        $requestHandler = $this->createHandler();

        $this->cache->method('get')
            ->willReturnCallback(function () {
                static $count = 0;
                $count++;

                return ['count' => $count - 1, 'start' => time()];
            });
        $this->cache->method('set')->willReturn(true);

        // Make 5 requests (within limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->rateLimitMiddleware->process($serverRequest, $requestHandler);
            $this->assertSame(200, $response->getStatusCode());
        }
    }

    public function test_blocks_requests_exceeding_limit(): void
    {
        $this->rateLimitMiddleware = new RateLimitMiddleware($this->cache, 3, 60);
        $serverRequest = $this->createRequest('127.0.0.1');
        $requestHandler = $this->createHandler();

        $this->cache->method('get')
            ->willReturnCallback(function () {
                static $count = 0;
                $count++;

                return ['count' => $count - 1, 'start' => time()];
            });
        $this->cache->method('set')->willReturn(true);

        // Make 3 requests (at limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->rateLimitMiddleware->process($serverRequest, $requestHandler);
            $this->assertSame(200, $response->getStatusCode());
        }

        // 4th request should be blocked
        $response = $this->rateLimitMiddleware->process($serverRequest, $requestHandler);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('Too Many Requests', (string) $response->getBody());
    }

    public function test_adds_rate_limit_headers(): void
    {
        $this->rateLimitMiddleware = new RateLimitMiddleware($this->cache, 5, 60);
        $serverRequest = $this->createRequest('127.0.0.1');
        $requestHandler = $this->createHandler();

        $this->cache->method('get')->willReturn(['count' => 1, 'start' => time()]);
        $this->cache->method('set')->willReturn(true);

        $response = $this->rateLimitMiddleware->process($serverRequest, $requestHandler);

        $this->assertTrue($response->hasHeader('X-RateLimit-Limit'));
        $this->assertTrue($response->hasHeader('X-RateLimit-Remaining'));
        $this->assertEquals('5', $response->getHeaderLine('X-RateLimit-Limit'));
    }

    public function test_different_ips_have_separate_limits(): void
    {
        $this->rateLimitMiddleware = new RateLimitMiddleware($this->cache, 2, 60);
        $requestHandler = $this->createHandler();

        $serverRequest = $this->createRequest('127.0.0.1');
        $request2 = $this->createRequest('192.168.1.1');

        $this->cache->method('get')
            ->willReturnCallback(function (string $key) {
                if (str_contains($key, '127.0.0.1')) {
                    static $count1 = 0;
                    $count1++;

                    return ['count' => $count1 - 1, 'start' => time()];
                }

                return ['count' => 0, 'start' => time()];
            });
        $this->cache->method('set')->willReturn(true);

        // IP 1 uses its limit
        $this->rateLimitMiddleware->process($serverRequest, $requestHandler);
        $this->rateLimitMiddleware->process($serverRequest, $requestHandler);

        // IP 2 should still be able to make requests
        $response = $this->rateLimitMiddleware->process($request2, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
    }

    private function createRequest(string $ip): ServerRequestInterface
    {
        $serverParams = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/test',
            'REMOTE_ADDR' => $ip,
        ];

        return $this->serverRequestFactory->createServerRequest('POST', '/test', $serverParams);
    }

    private function createHandler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturn(new Response(200));

        return $handler;
    }
}
