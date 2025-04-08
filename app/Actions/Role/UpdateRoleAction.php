<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Actions\Role;

use App\DTO\Role\UpdateRoleDTO;
use App\Interface\Role\UpdateRoleActionInterface;
use App\Models\Role;

final class UpdateRoleAction implements UpdateRoleActionInterface
{
    public function execute(UpdateRoleDTO $dto): ?array
    {
        $role = Role::findOrFail($dto->id);

        $role->update([
            'name' => $dto->name,
        ]);

        if (! empty($dto->permissions)) {
            $role->givePermissionTo($dto->permissions);
        }

        return $role->fresh()->load('permissions')->toArray();
    }
}
