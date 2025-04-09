<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\DTO\Role\CreateRoleDTO;
use App\Interface\Role\CreateRoleActionInterface;
use App\Models\Role;

final class CreateRoleAction implements CreateRoleActionInterface
{
    public function execute(CreateRoleDTO $dto): ?array
    {
        $role = Role::create([
            'name' => $dto->name,
        ]);

        if (! empty($dto->permissions)) {
            $role->givePermissionTo($dto->permissions);
        }

        return $role->load('permissions');
    }
}
