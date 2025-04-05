<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Session\Session;

class Auth
{
    protected Session $session;

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

        // Store user data in session
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
        $userData = $this->session->get('user');

        return $userData ? User::find($userData['id']) : null;
    }

    public function check(): bool
    {
        return $this->user() instanceof User;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }
}
