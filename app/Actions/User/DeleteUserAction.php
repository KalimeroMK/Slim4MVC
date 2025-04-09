<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

final class DeleteUserAction
{
    public function execute(int $id): void
    {
        $role = User::findOrFail($id);
        $role->delete();
    }
}
