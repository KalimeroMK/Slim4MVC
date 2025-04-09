<?php

declare(strict_types=1);

namespace App\Interface\Permission;

use App\DTO\Permission\UpdatePermissionDTO;
use RuntimeException;

interface UpdatePermissionActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(UpdatePermissionDTO $dto): ?array;
}
