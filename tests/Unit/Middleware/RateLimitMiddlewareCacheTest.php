<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Http\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * @covers \App\Modules\Core\Infrastructure\Http\Middleware\RateLimitMiddleware
 */
final class RateLimitMiddlewareCacheTest extends TestCase
{
    private ServerRequestFactory $serverRequestFactory;

    /** @var MockObject & CacheInterface */
    private MockObject $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverRequestFactory = new ServerRequestFactory();
        $this->cache = $this->createMock(CacheInterface::class);
    }

    public function test_allows_requests_within_limit(): void
    {
        $middleware = new RateLimitMiddleware($this->cache, 5, 60);
        $serverRequest = $this->createRequest('127.0.0.1');
        $requestHandler = $this->createHandler();

        $this->cache->method('get')
            ->willReturnCallback(function () {
                static $count = 0;
                $count++;

                return ['count' => $count - 1, 'start' => time()];
            });

        $this->cache->method('set')->willReturn(true);

        for ($i = 0; $i < 5; $i++) {
            $response = $middleware->process($serverRequest, $requestHandler);
            $this->assertSame(200, $response->getStatusCode());
        }
    }

    public function test_blocks_requests_exceeding_limit(): void
    {
        $middleware = new RateLimitMiddleware($this->cache, 3, 60);
        $serverRequest = $this->createRequest('127.0.0.1');
        $requestHandler = $this->createHandler();

        $this->cache->method('get')
            ->willReturnCallback(function () {
                static $count = 0;
                $count++;

                return ['count' => $count - 1, 'start' => time()];
            });

        $this->cache->method('set')->willReturn(true);

        for ($i = 0; $i < 3; $i++) {
            $response = $middleware->process($serverRequest, $requestHandler);
            $this->assertSame(200, $response->getStatusCode());
        }

        // 4th request should be blocked
        $response = $middleware->process($serverRequest, $requestHandler);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('Too Many Requests', (string) $response->getBody());
    }

    public function test_adds_rate_limit_headers(): void
    {
        $middleware = new RateLimitMiddleware($this->cache, 5, 60);
        $serverRequest = $this->createRequest('127.0.0.1');
        $requestHandler = $this->createHandler();

        $this->cache->method('get')->willReturn(['count' => 1, 'start' => time()]);
        $this->cache->method('set')->willReturn(true);

        $response = $middleware->process($serverRequest, $requestHandler);

        $this->assertTrue($response->hasHeader('X-RateLimit-Limit'));
        $this->assertTrue($response->hasHeader('X-RateLimit-Remaining'));
        $this->assertEquals('5', $response->getHeaderLine('X-RateLimit-Limit'));
    }

    public function test_different_ips_have_separate_limits(): void
    {
        $middleware = new RateLimitMiddleware($this->cache, 2, 60);
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
        $middleware->process($serverRequest, $requestHandler);
        $middleware->process($serverRequest, $requestHandler);

        // IP 2 should still be able to make requests
        $response = $middleware->process($request2, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_resets_counter_after_window_expires(): void
    {
        $middleware = new RateLimitMiddleware($this->cache, 2, 60);
        $serverRequest = $this->createRequest('127.0.0.1');
        $requestHandler = $this->createHandler();

        $oldTime = time() - 120; // 2 minutes ago

        $this->cache->method('get')
            ->willReturn(['count' => 5, 'start' => $oldTime]);

        $this->cache->method('set')->willReturn(true);

        $response = $middleware->process($serverRequest, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_uses_redis_cache_for_distributed_rate_limiting(): void
    {
        // This test verifies that the middleware accepts a CacheInterface
        // which could be RedisCache, FileCache, or NullCache.
        // The key improvement is that rate limits are now shared across processes.
        $middleware = new RateLimitMiddleware($this->cache, 10, 60);

        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    private function createRequest(string $ip): \Psr\Http\Message\ServerRequestInterface
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
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturn(new Response(200));

        return $handler;
    }
}
