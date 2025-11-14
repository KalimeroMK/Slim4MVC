<?php

declare(strict_types=1);

namespace App\Modules\User\Policies;

use App\Modules\User\Infrastructure\Models\User;

class UserPolicy extends \App\Modules\Core\Infrastructure\Policies\Policy
{
    /**
     * Determine if the given user can view the target user.
     */
    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->hasPermission('view-users');
    }

    /**
     * Determine if the given user can update the target user.
     */
    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->hasPermission('edit-users');
    }

    /**
     * Determine if the given user can delete the target user.
     */
    public function delete(User $user, User $target): bool
    {
        // Users can't delete themselves, only admins with delete-users permission can delete users
        return $user->id !== $target->id && $user->hasPermission('delete-users');
    }

    /**
     * Determine if the given user can create new users.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create-users');
    }
}
