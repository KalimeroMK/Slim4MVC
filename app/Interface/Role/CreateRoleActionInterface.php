<?php

declare(strict_types=1);

namespace App\Interface\Role;

use App\DTO\Role\CreateRoleDTO;
use RuntimeException;

interface CreateRoleActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(CreateRoleDTO $dto): ?array;
}
