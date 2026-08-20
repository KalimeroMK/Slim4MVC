<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\User\Infrastructure\Models\User;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Unified auth facade that delegates to SessionAuth (web) and JwtAuth (API).
 *
 * Call setRequest() once per request cycle (e.g. in AuthMiddleware) so that
 * JWT resolution uses the PSR-7 object instead of $_SERVER superglobals.
 *
 * Pass $allowSession = false for token-only guards. CSRF protection is skipped
 * for /api/ routes, so letting a session cookie authenticate an API request
 * would make every API endpoint reachable cross-site.
 */
class Auth
{
    protected ?User $user = null;

    protected ?ServerRequestInterface $request = null;

    /**
     * Whether the session cookie may authenticate the current request.
     * Disabled by token-only guards such as AuthMiddleware.
     */
    protected bool $sessionEnabled = true;

    private readonly SessionAuth $sessionAuth;

    private readonly JwtAuth $jwtAuth;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(Container $container)
    {
        $session = $container->get(Session::class);
        $jwtService = $container->get(JwtService::class);

        $logger = null;
        try {
            $logger = $container->get(LoggerInterface::class);
        } catch (DependencyException|NotFoundException) {
            // Logger is optional
        }

        $this->sessionAuth = new SessionAuth($session);
        $this->jwtAuth = new JwtAuth($jwtService, $logger);
    }

    /**
     * Bind the current PSR-7 request so JWT auth reads from it instead of $_SERVER.
     *
     * @param  bool  $allowSession  Set false for token-only guards (API routes) so a
     *                              session cookie cannot authenticate the request.
     */
    public function setRequest(ServerRequestInterface $request, bool $allowSession = true): void
    {
        $this->request = $request;
        $this->sessionEnabled = $allowSession;
        $this->user = null; // reset cached user when request changes
    }

    /**
     * Attempt session-based login (web).
     */
    public function attempt(string $email, string $password): bool
    {
        return $this->sessionAuth->attempt($email, $password);
    }

    /**
     * Destroy the session (web logout).
     */
    public function logout(): void
    {
        $this->sessionAuth->logout();
    }

    /**
     * Return the currently authenticated user, checking session first then JWT.
     */
    public function user(): ?User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        // 1. Session (web) — skipped for token-only guards
        if ($this->sessionEnabled) {
            $user = $this->sessionAuth->user();
            if ($user instanceof User) {
                $this->user = $user;

                return $this->user;
            }
        }

        $user = null;

        // 2. JWT via PSR-7 request (API)
        if ($this->request instanceof ServerRequestInterface) {
            $user = $this->jwtAuth->userFromRequest($this->request);
        } else {
            // Fallback: read Authorization header from $_SERVER when no request is bound
            $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (str_starts_with($header, 'Bearer ')) {
                $token = trim(substr($header, 7));
                $user = $this->jwtAuth->userFromToken($token);
            }
        }

        $this->user = $user;

        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * Expose the underlying JWT authenticator (e.g. for refresh token flows).
     */
    public function jwt(): JwtAuth
    {
        return $this->jwtAuth;
    }

    /**
     * Expose the underlying session authenticator (e.g. for web controllers).
     */
    public function session(): SessionAuth
    {
        return $this->sessionAuth;
    }
}
