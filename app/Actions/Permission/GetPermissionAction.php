<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Models\Permission;
use App\Models\Role;

final class GetPermissionAction
{
    public function execute(int $id): Role
    {
        return Permission::with('roles')->findOrFail($id);
    }
}
