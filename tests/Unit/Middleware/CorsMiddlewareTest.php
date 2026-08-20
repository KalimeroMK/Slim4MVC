<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Modules\Core\Infrastructure\Http\Middleware\CorsMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

#[CoversClass(CorsMiddleware::class)]
final class CorsMiddlewareTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function test_allowlisted_origin_is_echoed_back(): void
    {
        $middleware = $this->middleware(['origin' => ['https://app.example.com']]);

        $response = $middleware->process(
            $this->request('https://app.example.com'),
            $this->handler()
        );

        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function test_unknown_origin_receives_no_allow_origin_header(): void
    {
        $middleware = $this->middleware(['origin' => ['https://app.example.com']]);

        $response = $middleware->process(
            $this->request('https://evil.example.com'),
            $this->handler()
        );

        $this->assertFalse(
            $response->hasHeader('Access-Control-Allow-Origin'),
            'An origin outside the allowlist must never be reflected back.'
        );
    }

    public function test_wildcard_origin_sends_literal_star_not_the_request_origin(): void
    {
        $middleware = $this->middleware(['origin' => ['*']]);

        $response = $middleware->process(
            $this->request('https://evil.example.com'),
            $this->handler()
        );

        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function test_wildcard_origin_forces_credentials_off(): void
    {
        $middleware = $this->middleware([
            'origin' => ['*'],
            'credentials' => true,
        ]);

        $response = $middleware->process(
            $this->request('https://evil.example.com'),
            $this->handler()
        );

        $this->assertFalse(
            $response->hasHeader('Access-Control-Allow-Credentials'),
            'Wildcard origin combined with credentials would expose authenticated responses to any site.'
        );
        $this->assertFalse($middleware->allowsCredentials());
        $this->assertTrue($middleware->credentialsWereDowngraded());
    }

    public function test_credentials_are_allowed_with_an_explicit_origin_list(): void
    {
        $middleware = $this->middleware([
            'origin' => ['https://app.example.com'],
            'credentials' => true,
        ]);

        $response = $middleware->process(
            $this->request('https://app.example.com'),
            $this->handler()
        );

        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertTrue($middleware->allowsCredentials());
        $this->assertFalse($middleware->credentialsWereDowngraded());
    }

    public function test_credentials_are_not_advertised_to_a_rejected_origin(): void
    {
        $middleware = $this->middleware([
            'origin' => ['https://app.example.com'],
            'credentials' => true,
        ]);

        $response = $middleware->process(
            $this->request('https://evil.example.com'),
            $this->handler()
        );

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function test_wildcard_mixed_into_an_explicit_list_still_counts_as_wildcard(): void
    {
        $middleware = $this->middleware([
            'origin' => ['https://app.example.com', '*'],
            'credentials' => true,
        ]);

        $response = $middleware->process(
            $this->request('https://evil.example.com'),
            $this->handler()
        );

        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'));
    }

    public function test_request_without_origin_header_gets_no_allow_origin(): void
    {
        $middleware = $this->middleware(['origin' => ['https://app.example.com']]);

        $response = $middleware->process(
            $this->requestFactory->createServerRequest('GET', '/api/v1/users'),
            $this->handler()
        );

        $this->assertFalse(
            $response->hasHeader('Access-Control-Allow-Origin'),
            'A missing Origin header must not produce an empty Allow-Origin value.'
        );
    }

    public function test_empty_origin_config_allows_nothing(): void
    {
        $middleware = $this->middleware(['origin' => []]);

        $response = $middleware->process(
            $this->request('https://app.example.com'),
            $this->handler()
        );

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function test_origin_list_is_trimmed_and_accepts_a_comma_separated_string(): void
    {
        $middleware = $this->middleware(['origin' => ' https://app.example.com , https://admin.example.com ']);

        $first = $middleware->process($this->request('https://app.example.com'), $this->handler());
        $second = $middleware->process($this->request('https://admin.example.com'), $this->handler());

        $this->assertSame('https://app.example.com', $first->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('https://admin.example.com', $second->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function test_origin_matching_is_exact_and_rejects_suffix_lookalikes(): void
    {
        $middleware = $this->middleware(['origin' => ['https://app.example.com']]);

        foreach (['https://app.example.com.evil.net', 'https://evil-app.example.com', 'http://app.example.com'] as $origin) {
            $response = $middleware->process($this->request($origin), $this->handler());

            $this->assertFalse(
                $response->hasHeader('Access-Control-Allow-Origin'),
                sprintf('Origin %s must not match the allowlist.', $origin)
            );
        }
    }

    public function test_vary_origin_is_always_set(): void
    {
        $middleware = $this->middleware(['origin' => ['https://app.example.com']]);

        $response = $middleware->process(
            $this->request('https://evil.example.com'),
            $this->handler()
        );

        $this->assertSame('Origin', $response->getHeaderLine('Vary'));
    }

    public function test_preflight_short_circuits_without_calling_the_handler(): void
    {
        $middleware = $this->middleware(['origin' => ['https://app.example.com']]);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $request = $this->requestFactory
            ->createServerRequest('OPTIONS', '/api/v1/users')
            ->withHeader('Origin', 'https://app.example.com');

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function test_methods_allow_headers_and_expose_headers_are_advertised(): void
    {
        $middleware = $this->middleware([
            'origin' => ['https://app.example.com'],
            'methods' => ['GET', 'POST'],
            'headers.allow' => ['Content-Type'],
            'headers.expose' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
        ]);

        $response = $middleware->process($this->request('https://app.example.com'), $this->handler());

        $this->assertSame('GET, POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('Content-Type', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('X-RateLimit-Limit, X-RateLimit-Remaining', $response->getHeaderLine('Access-Control-Expose-Headers'));
    }

    public function test_expose_headers_omitted_when_empty(): void
    {
        $middleware = $this->middleware([
            'origin' => ['https://app.example.com'],
            'headers.expose' => [],
        ]);

        $response = $middleware->process($this->request('https://app.example.com'), $this->handler());

        $this->assertFalse($response->hasHeader('Access-Control-Expose-Headers'));
    }

    public function test_max_age_is_sent_when_positive_and_omitted_when_zero(): void
    {
        $withCache = $this->middleware(['origin' => ['*'], 'cache' => 600]);
        $withoutCache = $this->middleware(['origin' => ['*'], 'cache' => 0]);

        $this->assertSame(
            '600',
            $withCache->process($this->request('https://app.example.com'), $this->handler())
                ->getHeaderLine('Access-Control-Max-Age')
        );
        $this->assertFalse(
            $withoutCache->process($this->request('https://app.example.com'), $this->handler())
                ->hasHeader('Access-Control-Max-Age')
        );
    }

    public function test_defaults_to_wildcard_without_credentials(): void
    {
        $middleware = $this->middleware([]);

        $response = $middleware->process($this->request('https://anything.example.com'), $this->handler());

        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function middleware(array $options): CorsMiddleware
    {
        return new CorsMiddleware($this->responseFactory, $options);
    }

    private function request(string $origin): ServerRequestInterface
    {
        return $this->requestFactory
            ->createServerRequest('GET', '/api/v1/users')
            ->withHeader('Origin', $origin);
    }

    private function handler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200));

        return $handler;
    }
}
