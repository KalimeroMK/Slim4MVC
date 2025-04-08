<?php

namespace App\Interface\Role;

use App\DTO\Role\UpdateRoleDTO;
use RuntimeException;

interface UpdateRoleActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(UpdateRoleDTO $dto): ?array;
}