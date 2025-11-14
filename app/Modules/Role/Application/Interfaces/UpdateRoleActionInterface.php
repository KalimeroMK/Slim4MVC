<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Interfaces;

use App\Modules\Role\Application\DTOs\UpdateRoleDTO;
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
