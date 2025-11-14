<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Actions\Role;

use App\DTO\Role\UpdateRoleDTO;
use App\Interface\Role\UpdateRoleActionInterface;
use App\Models\Role;
use App\Repositories\RoleRepository;

final class UpdateRoleAction implements UpdateRoleActionInterface
{
    public function __construct(
        private readonly RoleRepository $repository
    ) {}

    /**
     * Execute role update.
     *
     * @return array<string, mixed>|null
     */
    public function execute(UpdateRoleDTO $dto): ?array
    {
        $attributes = [];
        if ($dto->name !== null) {
            $attributes['name'] = $dto->name;
        }

        $role = $this->repository->update($dto->id, $attributes);

        if ($dto->permissions !== []) {
            /** @var Role $role */
            $role->syncPermissions($dto->permissions);
        }

        return $role->fresh()->load('permissions')->toArray();
    }
}
