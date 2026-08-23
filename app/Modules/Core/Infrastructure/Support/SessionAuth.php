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

        // AuthHelper::setUser() reads 'roles' and 'permissions' out of this array to fill
        // $_SESSION, which is what hasRole() and can() consult. Leaving them out did not
        // fail anywhere visible - it just made every role check answer false for a user
        // who holds the role, so any page behind a role guard returned 403 after a web
        // login. JWT logins were unaffected, which is why it went unnoticed.
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'roles' => $user->roles()->pluck('name')->all(),
            'permissions' => $user->permissions()->pluck('name')->unique()->values()->all(),
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
