<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\DTO\Permission\CreatePermissionDTO;
use App\Models\Permission;

final class CreatePermissionAction
{
    public function execute(CreatePermissionDTO $dto): Permission
    {
        return Permission::create([
            'name' => $dto->name,
        ]);
    }
}