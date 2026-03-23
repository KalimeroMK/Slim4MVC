<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Interfaces;

use App\Modules\Role\Application\DTOs\CreateRoleDTO;
use App\Modules\Role\Infrastructure\Models\Role;
use RuntimeException;

interface CreateRoleActionInterface
{
    /**
     * Create a new role
     *
     * @throws RuntimeException On creation failure
     */
    public function execute(CreateRoleDTO $createRoleDTO): Role;
}
