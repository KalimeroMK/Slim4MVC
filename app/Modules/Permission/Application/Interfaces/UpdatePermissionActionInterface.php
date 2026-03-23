<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Interfaces;

use App\Modules\Permission\Application\DTOs\UpdatePermissionDTO;
use App\Modules\Permission\Infrastructure\Models\Permission;
use RuntimeException;

interface UpdatePermissionActionInterface
{
    /**
     * Update an existing permission
     *
     * @throws RuntimeException On update failure
     */
    public function execute(UpdatePermissionDTO $updatePermissionDTO): Permission;
}
