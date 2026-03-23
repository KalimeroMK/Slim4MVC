<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Interfaces;

use App\Modules\Permission\Application\DTOs\CreatePermissionDTO;
use App\Modules\Permission\Infrastructure\Models\Permission;
use RuntimeException;

interface CreatePermissionActionInterface
{
    /**
     * Create a new permission
     *
     * @throws RuntimeException On creation failure
     */
    public function execute(CreatePermissionDTO $createPermissionDTO): Permission;
}
