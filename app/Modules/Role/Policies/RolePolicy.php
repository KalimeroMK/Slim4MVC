<?php

declare(strict_types=1);

namespace App\Modules\Role\Policies;

use App\Modules\Core\Infrastructure\Policies\Policy;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;

class RolePolicy extends Policy
{
    /**
     * Determine if the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        // Add your authorization logic here
        // Example: return $user->hasPermission('view-role');
        return true;
    }

    /**
     * Determine if the user can create roles.
     */
    public function create(User $user): bool
    {
        // Add your authorization logic here
        // Example: return $user->hasPermission('create-role');
        return true;
    }

    /**
     * Determine if the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        // Add your authorization logic here
        // Example: return $user->hasPermission('update-role') || $user->id === $role->user_id;
        return true;
    }

    /**
     * Determine if the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        // Add your authorization logic here
        // Example: return $user->hasPermission('delete-role');
        return true;
    }
}
