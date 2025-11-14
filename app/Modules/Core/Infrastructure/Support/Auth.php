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
        } catch (DependencyException|NotFoundException $e) {
            // Logger not available, continue without it
        }

        try {
            $this->jwtService = $container->get(JwtService::class);
        } catch (DependencyException|NotFoundException $e) {
            // JwtService will be created on demand if not in container
            $this->jwtService = new JwtService();
        }
    }

    public function attempt(string $email, string $password): bool
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! password_verify($password, $user->password)) {
            return false;
        }

        $this->session->migrate(true);

        $this->session->set('user', [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ]);

        $this->session->save();

        return true;
    }

    public function logout(): void
    {
        $this->session->remove('user');
    }

    public function user(): ?User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (! str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = mb_trim(str_replace('Bearer', '', $authHeader));

        try {
            $decoded = $this->jwtService->decode($token);
            $this->user = User::find($decoded->id);

            return $this->user;
        } catch (Exception $e) {
            // Log authentication failures for security monitoring
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('JWT authentication failed', [
                    'error' => $e->getMessage(),
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
