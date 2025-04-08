<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Models\Permission;

final class DeletePermissionAction
{
    public function execute(int $id): void
    {
        $role = Permission::findOrFail($id);
        $role->delete();
    }
}
