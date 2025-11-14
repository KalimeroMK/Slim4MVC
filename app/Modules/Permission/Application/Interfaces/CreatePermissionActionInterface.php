<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Interfaces;

use App\Modules\Permission\Application\DTOs\CreatePermissionDTO;
use RuntimeException;

interface CreatePermissionActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(CreatePermissionDTO $dto): ?array;
}
