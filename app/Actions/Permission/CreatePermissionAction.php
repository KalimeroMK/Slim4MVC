<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\DTO\Permission\CreatePermissionDTO;
use App\Interface\Permission\CreatePermissionActionInterface;
use App\Models\Permission;

final class CreatePermissionAction implements CreatePermissionActionInterface
{
    public function execute(CreatePermissionDTO $dto): Permission
    {
        return Permission::create([
            'name' => $dto->name,
        ]);
    }
}
