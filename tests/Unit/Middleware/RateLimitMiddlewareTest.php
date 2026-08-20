<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Http\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

#[CoversClass(RateLimitMiddleware::class)]
final class RateLimitMiddlewareTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    /** @var array<string, int> counter keys seen by the fake cache */
    private array $counters = [];

    /** @var array<string, int|null> ttl passed alongside each increment */
    private array $ttls = [];

    private ?string $originalTrustedProxies = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory();
        $this->counters = [];
        $this->ttls = [];
        $this->originalTrustedProxies = $_ENV['TRUSTED_PROXIES'] ?? null;
        unset($_ENV['TRUSTED_PROXIES']);
    }

    protected function tearDown(): void
    {
        if ($this->originalTrustedProxies === null) {
            unset($_ENV['TRUSTED_PROXIES']);
        } else {
            $_ENV['TRUSTED_PROXIES'] = $this->originalTrustedProxies;
        }

        parent::tearDown();
    }

    public function test_requests_within_the_limit_pass(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 5, 60);
        $request = $this->request('127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $response = $middleware->process($request, $this->handler());
            $this->assertSame(200, $response->getStatusCode());
        }
    }

    public function test_the_request_at_the_limit_still_passes(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 3, 60);
        $request = $this->request('127.0.0.1');

        $middleware->process($request, $this->handler());
        $middleware->process($request, $this->handler());
        $response = $middleware->process($request, $this->handler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('0', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function test_the_request_past_the_limit_is_rejected(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 3, 60);
        $request = $this->request('127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            $middleware->process($request, $this->handler());
        }

        $response = $middleware->process($request, $this->handler());

        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('Too Many Requests', (string) $response->getBody());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('0', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function test_rejection_advertises_retry_after_and_reset(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 1, 60);
        $request = $this->request('127.0.0.1');

        $middleware->process($request, $this->handler());
        $response = $middleware->process($request, $this->handler());

        $retryAfter = (int) $response->getHeaderLine('Retry-After');
        $reset = (int) $response->getHeaderLine('X-RateLimit-Reset');

        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
        $this->assertGreaterThan(time(), $reset);
    }

    public function test_successful_responses_carry_the_remaining_count(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 5, 60);
        $request = $this->request('127.0.0.1');

        $first = $middleware->process($request, $this->handler());
        $second = $middleware->process($request, $this->handler());

        $this->assertSame('5', $first->getHeaderLine('X-RateLimit-Limit'));
        $this->assertSame('4', $first->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertSame('3', $second->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function test_each_client_ip_gets_its_own_counter(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 2, 60);

        $middleware->process($this->request('127.0.0.1'), $this->handler());
        $middleware->process($this->request('127.0.0.1'), $this->handler());
        $middleware->process($this->request('127.0.0.1'), $this->handler());

        $response = $middleware->process($this->request('192.168.1.1'), $this->handler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $this->counters, 'Each identifier must get a distinct cache key.');
    }

    public function test_the_counter_key_is_scoped_to_the_current_window(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 5, 60);
        $middleware->process($this->request('127.0.0.1'), $this->handler());

        $key = array_key_first($this->counters);

        $this->assertNotNull($key);
        $this->assertSame(
            sprintf('rate_limit:127.0.0.1:%d', intdiv(time(), 60)),
            $key,
            'A window-scoped key is what lets the counter expire instead of being reset by hand.'
        );
    }

    public function test_the_counter_is_created_with_a_ttl(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 5, 60);
        $middleware->process($this->request('127.0.0.1'), $this->handler());

        $this->assertSame([120], array_values(array_unique($this->ttls)));
    }

    public function test_it_fails_open_when_the_driver_cannot_count(): void
    {
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('increment')->willReturn(false);

        $middleware = new RateLimitMiddleware($cache, 1, 60);

        $first = $middleware->process($this->request('127.0.0.1'), $this->handler());
        $second = $middleware->process($this->request('127.0.0.1'), $this->handler());

        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame(200, $second->getStatusCode(), 'A broken cache must not lock every client out.');
    }

    public function test_forwarded_for_is_ignored_for_an_untrusted_remote_address(): void
    {
        $middleware = new RateLimitMiddleware($this->cache(), 5, 60);

        $request = $this->request('127.0.0.1')->withHeader('X-Forwarded-For', '203.0.113.9');
        $middleware->process($request, $this->handler());

        $this->assertSame(
            [sprintf('rate_limit:127.0.0.1:%d', intdiv(time(), 60))],
            array_keys($this->counters),
            'A spoofable header must not let a client pick its own rate-limit bucket.'
        );
    }

    public function test_forwarded_for_is_honoured_for_a_trusted_proxy(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';
        $middleware = new RateLimitMiddleware($this->cache(), 5, 60);

        $request = $this->request('10.0.0.1')->withHeader('X-Forwarded-For', '203.0.113.9, 10.0.0.1');
        $middleware->process($request, $this->handler());

        $this->assertSame(
            [sprintf('rate_limit:203.0.113.9:%d', intdiv(time(), 60))],
            array_keys($this->counters)
        );
    }

    public function test_a_malformed_forwarded_for_falls_back_to_the_remote_address(): void
    {
        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';
        $middleware = new RateLimitMiddleware($this->cache(), 5, 60);

        $request = $this->request('10.0.0.1')->withHeader('X-Forwarded-For', 'not-an-ip');
        $middleware->process($request, $this->handler());

        $this->assertSame(
            [sprintf('rate_limit:10.0.0.1:%d', intdiv(time(), 60))],
            array_keys($this->counters)
        );
    }

    /**
     * A cache double that counts like Redis INCRBY: one atomic step per call.
     */
    private function cache(): CacheInterface
    {
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('increment')->willReturnCallback(
            function (string $key, int $value = 1, ?int $ttl = null): int {
                $this->counters[$key] = ($this->counters[$key] ?? 0) + $value;
                $this->ttls[$key] = $ttl;

                return $this->counters[$key];
            }
        );

        return $cache;
    }

    private function request(string $ip): ServerRequestInterface
    {
        return $this->requestFactory->createServerRequest('POST', '/api/v1/test', [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/v1/test',
            'REMOTE_ADDR' => $ip,
        ]);
    }

    private function handler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200));

        return $handler;
    }
}
