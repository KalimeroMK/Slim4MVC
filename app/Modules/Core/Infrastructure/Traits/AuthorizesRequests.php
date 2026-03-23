<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Traits;

use App\Modules\Core\Infrastructure\Support\AuthHelper;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait for authorization checks in controllers.
 */
trait AuthorizesRequests
{
    /**
     * Check if the user is authorized to perform an action.
     */
    protected function authorize(string $ability, mixed $resource = null): bool
    {
        // Simple authorization check - can be extended with policies
        if (! AuthHelper::check()) {
            return false;
        }

        // TODO: Implement policy checks here
        return true;
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
}
