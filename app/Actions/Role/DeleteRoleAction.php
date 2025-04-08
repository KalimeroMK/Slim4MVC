<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Models\Role;

final class DeleteRoleAction
{
    public function execute(int $id): void
    {
        $role = Role::findOrFail($id);
        $role->delete();
    }
}