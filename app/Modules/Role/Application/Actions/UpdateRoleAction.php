<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Application\DTOs\UpdateRoleDTO;
use App\Modules\Role\Application\Interfaces\UpdateRoleActionInterface;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

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
