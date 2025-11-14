<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AuthMiddleware;
use App\Models\User;
use App\Support\Auth;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;

    private MockObject $auth;

    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory();
        $this->auth = $this->createMock(Auth::class);
        $this->middleware = new AuthMiddleware($this->auth);
        $_ENV['JWT_SECRET'] = 'test-secret-key';
    }

    public function test_process_allows_request_when_user_is_authenticated(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $this->auth->method('check')->willReturn(true);
        $this->auth->method('user')->willReturn($user);

        $request = $this->requestFactory->createServerRequest('GET', '/api/test');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_process_blocks_request_when_user_is_not_authenticated(): void
    {
        $this->auth->method('check')->willReturn(false);

        $request = $this->requestFactory->createServerRequest('GET', '/api/test');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Unauthorized', (string) $response->getBody());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function test_process_returns_json_error_response(): void
    {
        $this->auth->method('check')->willReturn(false);

        $request = $this->requestFactory->createServerRequest('GET', '/api/test');
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('status', $body);
        $this->assertEquals('error', $body['status']);
        $this->assertArrayHasKey('message', $body);
        $this->assertEquals('Unauthorized', $body['message']);
        $this->assertArrayHasKey('code', $body);
        $this->assertEquals('UNAUTHORIZED', $body['code']);
    }

    private function createHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturn(new Response(200));

        return $handler;
    }
}
