<?php

declare(strict_types=1);

namespace App\Modules\Permission\Policies;

use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\User\Infrastructure\Models\User;

class PermissionPolicy extends \App\Modules\Core\Infrastructure\Policies\Policy
{
    /**
     * Determine if the user can view the permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermission('view-permissions');
    }

    /**
     * Determine if the user can create permissions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create-permissions');
    }

    /**
     * Determine if the user can update the permission.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermission('edit-permissions');
    }

    /**
     * Determine if the user can delete the permission.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasPermission('delete-permissions');
    }
}
