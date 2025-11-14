<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Policies;

use App\Modules\User\Infrastructure\Models\User;

abstract class Policy
{
    /**
     * Determine if the given user can perform any action.
     */
    final public function before(User $user): ?bool
    {
        // If user is super admin, allow all actions
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null; // null means fall through to the actual policy method
    }
}
