<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Interfaces;

use App\Modules\Permission\Application\DTOs\UpdatePermissionDTO;
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
