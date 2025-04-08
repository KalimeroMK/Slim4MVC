<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Actions\Role;

use App\DTO\Role\CreateRoleDTO;
use App\Interface\Permission\CreatePermissionActionInterface;
use App\Models\Role;

final class UpdateRolePermissionAction implements CreatePermissionActionInterface
{
    public function execute(CreateRoleDTO $dto): Role
    {
        $role = Role::findOrFail($dto->id);
        $role->update($dto->name);
        if (! empty($dto->permissions)) {
            $role->givePermissionTo($dto->permissions);
        }

        return $role->fresh()->load('permissions');
    }
}
