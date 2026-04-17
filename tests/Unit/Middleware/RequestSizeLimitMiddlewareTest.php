<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Modules\Core\Infrastructure\Http\Middleware\RequestSizeLimitMiddleware;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * @covers \App\Modules\Core\Infrastructure\Http\Middleware\RequestSizeLimitMiddleware
 */
final class RequestSizeLimitMiddlewareTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestFactory = new ServerRequestFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function test_allows_request_within_size_limit(): void
    {
        $middleware = new RequestSizeLimitMiddleware(1024); // 1KB limit
        $handler = $this->createHandler();

        $body = $this->streamFactory->createStream('small body');
        $request = $this->requestFactory->createServerRequest('POST', '/')
            ->withBody($body);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_blocks_request_exceeding_size_limit(): void
    {
        $middleware = new RequestSizeLimitMiddleware(10); // 10 bytes limit
        $handler = $this->createHandler();

        $body = $this->streamFactory->createStream('this body is way too large');
        $request = $this->requestFactory->createServerRequest('POST', '/')
            ->withBody($body);

        $response = $middleware->process($request, $handler);

        $this->assertSame(413, $response->getStatusCode());
        $bodyContent = json_decode((string) $response->getBody(), true);
        $this->assertStringContainsString('Request body too large', $bodyContent['message'] ?? '');
    }

    public function test_uses_content_length_header_when_available(): void
    {
        $middleware = new RequestSizeLimitMiddleware(100); // 100 bytes limit
        $handler = $this->createHandler();

        $request = $this->requestFactory->createServerRequest('POST', '/')
            ->withHeader('Content-Length', '200');

        $response = $middleware->process($request, $handler);

        $this->assertSame(413, $response->getStatusCode());
    }

    public function test_allows_request_when_content_length_within_limit(): void
    {
        $middleware = new RequestSizeLimitMiddleware(1000);
        $handler = $this->createHandler();

        $request = $this->requestFactory->createServerRequest('POST', '/')
            ->withHeader('Content-Length', '50');

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_default_limit_is_10mb(): void
    {
        $middleware = new RequestSizeLimitMiddleware();
        $handler = $this->createHandler();

        // 5MB body should pass with default 10MB limit
        $largeBody = str_repeat('x', 5 * 1024 * 1024);
        $body = $this->streamFactory->createStream($largeBody);
        $request = $this->requestFactory->createServerRequest('POST', '/')
            ->withBody($body);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_error_message_includes_formatted_size(): void
    {
        $middleware = new RequestSizeLimitMiddleware(1024);
        $handler = $this->createHandler();

        $body = $this->streamFactory->createStream(str_repeat('x', 2048));
        $request = $this->requestFactory->createServerRequest('POST', '/')
            ->withBody($body);

        $response = $middleware->process($request, $handler);
        $bodyContent = json_decode((string) $response->getBody(), true);

        $this->assertStringContainsString('1 KB', $bodyContent['message'] ?? '');
    }

    public function test_preserves_request_body_after_size_check(): void
    {
        $middleware = new RequestSizeLimitMiddleware(1024);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturnCallback(function ($request) {
                // Verify body is still readable
                $body = (string) $request->getBody();
                $this->assertSame('test content', $body);

                return new Response(200);
            });

        $body = $this->streamFactory->createStream('test content');
        $request = $this->requestFactory->createServerRequest('POST', '/')
            ->withBody($body);

        $response = $middleware->process($request, $handler);
        $this->assertSame(200, $response->getStatusCode());
    }

    private function createHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200));

        return $handler;
    }
}
