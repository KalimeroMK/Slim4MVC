<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Interfaces;

use App\Modules\Role\Application\DTOs\UpdateRoleDTO;
use App\Modules\Role\Infrastructure\Models\Role;
use RuntimeException;

interface UpdateRoleActionInterface
{
    /**
     * Update an existing role
     *
     * @throws RuntimeException On update failure
     */
    public function execute(UpdateRoleDTO $updateRoleDTO): Role;
}
