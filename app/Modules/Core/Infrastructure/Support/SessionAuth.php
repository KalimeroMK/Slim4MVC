<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\User\Infrastructure\Models\User;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Handles session-based authentication for web (browser) requests.
 */
final class SessionAuth
{
    public function __construct(
        private readonly Session $session
    ) {}

    /**
     * Attempt to authenticate via email + password.
     * On success the user is stored in the session.
     */
    public function attempt(string $email, string $password): bool
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user instanceof User || ! password_verify($password, (string) $user->password)) {
            return false;
        }

        $this->session->migrate(true);

        $userData = [
            'id'    => $user->id,
            'email' => $user->email,
            'name'  => $user->name,
        ];

        $this->session->set('user', $userData);
        AuthHelper::setUser($userData);
        $this->session->save();

        return true;
    }

    /**
     * Resolve the currently authenticated user from the session.
     */
    public function user(): ?User
    {
        $sessionUser = $this->session->get('user');

        if (! is_array($sessionUser) || ! isset($sessionUser['id'])) {
            return null;
        }

        /** @var User|null $user */
        $user = User::find($sessionUser['id']);

        return $user;
    }

    /**
     * Clear the authenticated user from the session.
     */
    public function logout(): void
    {
        $this->session->remove('user');
        AuthHelper::logout();
    }
}
