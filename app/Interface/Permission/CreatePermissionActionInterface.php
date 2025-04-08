<?php

declare(strict_types=1);

namespace App\Interface\Permission;

use App\DTO\Role\CreateRoleDTO;
use RuntimeException;

interface CreatePermissionActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(CreateRoleDTO $dto): ?array;
}
