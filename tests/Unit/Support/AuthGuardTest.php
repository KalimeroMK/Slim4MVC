<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Modules\Core\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\Core\Infrastructure\Support\JwtService;
use App\Modules\User\Infrastructure\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Tests\TestCase;

/**
 * Guards the boundary between cookie-session auth (web) and token auth (API).
 *
 * CSRF validation is skipped for /api/ paths, so a session cookie must never be
 * able to authenticate an API request — otherwise every API endpoint is reachable
 * from any other origin.
 */
#[CoversClass(Auth::class)]
#[CoversClass(AuthMiddleware::class)]
final class AuthGuardTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory();
        $this->user = User::create([
            'name' => 'Session User',
            'email' => 'session@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);
    }

    public function test_session_authenticates_a_web_request(): void
    {
        $this->loginViaSession();
        $auth = $this->auth();

        $auth->setRequest($this->requestFactory->createServerRequest('GET', '/dashboard'));

        $this->assertTrue($auth->check());
        $this->assertSame($this->user->id, $auth->user()?->id);
    }

    public function test_session_does_not_authenticate_when_session_is_disallowed(): void
    {
        $this->loginViaSession();
        $auth = $this->auth();

        $auth->setRequest(
            $this->requestFactory->createServerRequest('GET', '/api/v1/users'),
            allowSession: false
        );

        $this->assertFalse($auth->check(), 'A session cookie must not authenticate a token-only request.');
        $this->assertNull($auth->user());
    }

    public function test_auth_middleware_rejects_a_session_only_request(): void
    {
        $this->loginViaSession();

        $middleware = new AuthMiddleware($this->auth());
        $response = $middleware->process(
            $this->requestFactory->createServerRequest('GET', '/api/v1/users'),
            $this->handler()
        );

        $this->assertSame(
            401,
            $response->getStatusCode(),
            'AuthMiddleware guards /api/ routes, where CSRF is skipped, so cookies must not authenticate.'
        );
    }

    public function test_auth_middleware_accepts_a_valid_bearer_token(): void
    {
        $middleware = new AuthMiddleware($this->auth());
        $response = $middleware->process($this->bearerRequest($this->token()), $this->handler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_bearer_token_still_works_when_session_is_disallowed(): void
    {
        $auth = $this->auth();
        $auth->setRequest($this->bearerRequest($this->token()), allowSession: false);

        $this->assertTrue($auth->check());
        $this->assertSame($this->user->id, $auth->user()?->id);
    }

    public function test_session_login_does_not_leak_into_a_token_only_request_with_a_bad_token(): void
    {
        $this->loginViaSession();
        $auth = $this->auth();

        $auth->setRequest($this->bearerRequest('not-a-jwt'), allowSession: false);

        $this->assertFalse($auth->check());
    }

    public function test_set_request_resets_the_cached_user(): void
    {
        $this->loginViaSession();
        $auth = $this->auth();

        $auth->setRequest($this->requestFactory->createServerRequest('GET', '/dashboard'));
        $this->assertTrue($auth->check());

        // Same instance, now used as a token-only guard: the cache must not carry over.
        $auth->setRequest(
            $this->requestFactory->createServerRequest('GET', '/api/v1/users'),
            allowSession: false
        );

        $this->assertFalse($auth->check());
    }

    private function auth(): Auth
    {
        return new Auth($this->container);
    }

    private function loginViaSession(): void
    {
        /** @var Session $session */
        $session = $this->container->get(Session::class);
        $session->set('user', [
            'id' => $this->user->id,
            'email' => $this->user->email,
            'name' => $this->user->name,
        ]);
    }

    private function token(): string
    {
        /** @var JwtService $jwtService */
        $jwtService = $this->container->get(JwtService::class);

        return $jwtService->encode(['id' => $this->user->id], 3600);
    }

    private function bearerRequest(string $token): ServerRequestInterface
    {
        return $this->requestFactory
            ->createServerRequest('GET', '/api/v1/users')
            ->withHeader('Authorization', 'Bearer '.$token);
    }

    private function handler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200));

        return $handler;
    }
}
