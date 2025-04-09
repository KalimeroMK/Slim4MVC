<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

final class GetUserAction
{
    public function execute(int $id): User
    {
        return User::with('roles')->findOrFail($id);
    }
}
