<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

/**
 * Authentication helper for Blade templates.
 * Provides Laravel-like auth functionality for BladeOne.
 */
class AuthHelper
{
    private static ?array $user = null;

    /**
     * Check if user is authenticated.
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null;
    }

    /**
     * Check if user is guest (not authenticated).
     */
    public static function guest(): bool
    {
        return ! self::check();
    }

    /**
     * Get current authenticated user.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        if (! self::check()) {
            return null;
        }

        // Return basic user data from session
        // For full user model, use UserRepository
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
        ];
    }

    /**
     * Get current user ID.
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user has specific role.
     */
    public static function hasRole(string $role): bool
    {
        return in_array($role, $_SESSION['user_roles'] ?? [], true);
    }

    /**
     * Check if user has specific permission.
     */
    public static function can(string $permission): bool
    {
        return in_array($permission, $_SESSION['user_permissions'] ?? [], true);
    }

    /**
     * Set user data (used after login).
     *
     * @param array<string, mixed> $user
     */
    public static function setUser(array $user): void
    {
        self::$user = $user;
        $_SESSION['user_id'] = $user['id'] ?? null;
        $_SESSION['user_name'] = $user['name'] ?? null;
        $_SESSION['user_email'] = $user['email'] ?? null;
        $_SESSION['user_roles'] = $user['roles'] ?? [];
        $_SESSION['user_permissions'] = $user['permissions'] ?? [];
    }

    /**
     * Clear user data (used on logout).
     */
    public static function logout(): void
    {
        self::$user = null;
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
        unset($_SESSION['user_roles'], $_SESSION['user_permissions']);
    }

    /**
     * Generate CSRF token field.
     */
    public static function csrfField(): string
    {
        $token = $_SESSION['csrf_token'] ?? '';

        return '<input type="hidden" name="_token" value="'.htmlspecialchars($token, ENT_QUOTES, 'UTF-8').'">';
    }

    /**
     * Get CSRF token value.
     */
    public static function csrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Generate HTTP method spoofing field.
     */
    public static function methodField(string $method): string
    {
        $method = strtoupper($method);

        return '<input type="hidden" name="_method" value="'.htmlspecialchars($method, ENT_QUOTES, 'UTF-8').'">';
    }
}
