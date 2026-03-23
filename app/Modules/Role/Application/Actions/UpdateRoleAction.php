<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Application\DTOs\UpdateRoleDTO;
use App\Modules\Role\Application\Interfaces\UpdateRoleActionInterface;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final readonly class UpdateRoleAction implements UpdateRoleActionInterface
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * Execute role update.
     */
    public function execute(UpdateRoleDTO $updateRoleDTO): Role
    {
        $attributes = [];
        if ($updateRoleDTO->name !== null) {
            $attributes['name'] = $updateRoleDTO->name;
        }

        $this->roleRepository->update($updateRoleDTO->id, $attributes);

        /** @var Role $role */
        $role = Role::find($updateRoleDTO->id);

        if ($updateRoleDTO->permissions !== []) {
            $role->syncPermissions($updateRoleDTO->permissions);
        }

        return $role->load('permissions');
    }
}
