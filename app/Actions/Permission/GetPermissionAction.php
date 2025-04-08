<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Models\Permission;

final class GetPermissionAction
{
    public function execute($id): Permission
    {
        return Permission::with('roles')->findOrFail($id);
    }
}
