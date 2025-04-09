<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\DTO\Permission\UpdatePermissionDTO;
use App\Interface\Permission\UpdatePermissionActionInterface;
use App\Models\Permission;

final class UpdatePermissionAction implements UpdatePermissionActionInterface
{
    public function execute(UpdatePermissionDTO $dto): Permission
    {
        $permission = Permission::findOrFail($dto->id);

        $permission->update([
            'name' => $dto->name,
        ]);

        return $permission->fresh();
    }
}
