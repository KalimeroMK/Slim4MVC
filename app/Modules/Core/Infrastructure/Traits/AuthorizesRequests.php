<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Traits;

use App\Modules\Core\Infrastructure\Policies\Policy;
use App\Modules\Core\Infrastructure\Support\AuthHelper;
use App\Modules\User\Infrastructure\Models\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait for authorization checks in controllers.
 */
trait AuthorizesRequests
{
    /**
     * Check if the user is authorized to perform an action via policy or permission fallback.
     *
     * Usage:
     *   $this->authorize('create')           — checks if logged in (no policy needed)
     *   $this->authorize('update', $role)    — resolves RolePolicy::update()
     */
    protected function authorize(string $ability, mixed $resource = null): bool
    {
        $userId = AuthHelper::id();
        if ($userId === null) {
            return false;
        }

        /** @var User|null $user */
        $user = User::find($userId);
        if (! $user instanceof User) {
            return false;
        }

        $policy = is_object($resource) ? $this->resolvePolicy($resource) : null;

        if (! $policy instanceof Policy) {
            // No policy found — fall back to direct permission check
            return AuthHelper::can($ability);
        }

        // Super-admin bypass via Policy::before()
        $before = $policy->before($user);
        if ($before !== null) {
            return $before;
        }

        if (! method_exists($policy, $ability)) {
            return false;
        }

        return $policy->{$ability}($user, $resource);
    }

    /**
     * Check if the user has a specific permission.
     */
    protected function can(string $permission): bool
    {
        return AuthHelper::can($permission);
    }

    /**
     * Check if the user has a specific role.
     */
    protected function hasRole(string $role): bool
    {
        return AuthHelper::hasRole($role);
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorized(?string $message = null): ResponseInterface
    {
        return \App\Modules\Core\Infrastructure\Support\ApiResponse::unauthorized($message ?? 'Unauthorized');
    }

    /**
     * Resolve the policy class for a given model instance.
     *
     * Follows the naming convention:
     *   App\Modules\Foo\Infrastructure\Models\Bar → App\Modules\Foo\Policies\BarPolicy
     */
    private function resolvePolicy(object $resource): ?Policy
    {
        $modelClass = get_class($resource);
        $policyClass = str_replace('\\Infrastructure\\Models\\', '\\Policies\\', $modelClass).'Policy';

        if (! class_exists($policyClass)) {
            return null;
        }

        $policy = new $policyClass();

        return $policy instanceof Policy ? $policy : null;
    }
}
