<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Application\DTOs\UpdateRoleDTO;
use App\Modules\Role\Application\Interfaces\UpdateRoleActionInterface;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final readonly class UpdateRoleAction implements UpdateRoleActionInterface
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * Execute role update.
     *
     * @return array<string, mixed>|null
     */
    public function execute(UpdateRoleDTO $updateRoleDTO): ?array
    {
        $attributes = [];
        if ($updateRoleDTO->name !== null) {
            $attributes['name'] = $updateRoleDTO->name;
        }

        $role = $this->roleRepository->update($updateRoleDTO->id, $attributes);

        if ($updateRoleDTO->permissions !== []) {
            /** @var Role $role */
            $role->syncPermissions($updateRoleDTO->permissions);
        }

        return $role->fresh()->load('permissions')->toArray();
    }
}
