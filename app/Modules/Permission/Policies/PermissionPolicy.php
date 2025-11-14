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
        // Add your authorization logic here
        // Example: return $user->hasPermission('view-permission');
        return true;
    }

    /**
     * Determine if the user can create permissions.
     */
    public function create(User $user): bool
    {
        // Add your authorization logic here
        // Example: return $user->hasPermission('create-permission');
        return true;
    }

    /**
     * Determine if the user can update the permission.
     */
    public function update(User $user, Permission $permission): bool
    {
        // Add your authorization logic here
        // Example: return $user->hasPermission('update-permission') || $user->id === $permission->user_id;
        return true;
    }

    /**
     * Determine if the user can delete the permission.
     */
    public function delete(User $user, Permission $permission): bool
    {
        // Add your authorization logic here
        // Example: return $user->hasPermission('delete-permission');
        return true;
    }
}
