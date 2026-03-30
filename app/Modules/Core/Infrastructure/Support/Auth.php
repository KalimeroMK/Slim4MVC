<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\User\Infrastructure\Models\User;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class Auth
{
    protected Session $session;

    protected ?User $user = null;

    protected ?LoggerInterface $logger = null;

    protected JwtService $jwtService;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(Container $container)
    {
        $this->session = $container->get(Session::class);

        try {
            $this->logger = $container->get(LoggerInterface::class);
        } catch (DependencyException|NotFoundException) {
            // Logger not available, continue without it
        }

        try {
            $this->jwtService = $container->get(JwtService::class);
        } catch (DependencyException|NotFoundException) {
            // JwtService will be created on demand if not in container
            $this->jwtService = new JwtService();
        }
    }

    public function attempt(string $email, string $password): bool
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user instanceof User || ! password_verify($password, (string) $user->password)) {
            return false;
        }

        $this->session->migrate(true);

        // Store user data in session
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ];
        $this->session->set('user', $userData);

        // Also store in native $_SESSION for AuthHelper compatibility
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->name;

        $this->session->save();

        return true;
    }

    public function logout(): void
    {
        $this->session->remove('user');
        unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name']);
    }

    public function user(): ?User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        // First check session (for web authentication)
        $sessionUser = $this->session->get('user');
        if (is_array($sessionUser) && isset($sessionUser['id'])) {
            /** @var User|null $user */
            $user = User::find($sessionUser['id']);
            if ($user instanceof User) {
                $this->user = $user;

                return $this->user;
            }
        }

        // Then check JWT (for API authentication)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (! str_starts_with((string) $authHeader, 'Bearer ')) {
            return null;
        }

        $token = mb_trim(str_replace('Bearer', '', $authHeader));

        try {
            $decoded = $this->jwtService->decode($token);
            /** @var User|null $user */
            $user = User::find($decoded->id);
            $this->user = $user;

            return $this->user;
        } catch (Exception $exception) {
            // Log authentication failures for security monitoring
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('JWT authentication failed', [
                    'error' => $exception->getMessage(),
                    'token_preview' => mb_substr($token, 0, 20).'...',
                ]);
            }

            return null;
        }
    }

    public function check(): bool
    {
        return $this->user() instanceof User;
    }
}
