<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Models\Role;

final class GetRoleAction
{
    public function execute(int $id): Role
    {
        return Role::with('permissions')->findOrFail($id);
    }
}