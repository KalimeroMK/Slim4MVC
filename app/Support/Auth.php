<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Session\Session;

class Auth
{
    protected Session $session;

    protected ?User $user = null;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(Container $container)
    {
        $this->session = $container->get(Session::class);
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
        if ($this->user instanceof \App\Models\User) {
            return $this->user;
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (! str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(str_replace('Bearer', '', $authHeader));

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $this->user = User::find($decoded->id);

            return $this->user;
        } catch (Exception $e) {
            return null;
        }
    }

    public function check(): bool
    {
        return $this->user() instanceof User;
    }
}
