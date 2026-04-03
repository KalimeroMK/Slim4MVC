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
        return $user->hasPermission('view-roles');
    }

    /**
     * Determine if the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create-roles');
    }

    /**
     * Determine if the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('edit-roles');
    }

    /**
     * Determine if the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('delete-roles');
    }
}
