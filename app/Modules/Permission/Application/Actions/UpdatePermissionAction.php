<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Actions;

use App\Modules\Permission\Application\DTOs\UpdatePermissionDTO;
use App\Modules\Permission\Application\Interfaces\UpdatePermissionActionInterface;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;

final readonly class UpdatePermissionAction implements UpdatePermissionActionInterface
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * Execute permission update.
     */
    public function execute(UpdatePermissionDTO $updatePermissionDTO): Permission
    {
        $attributes = [];
        if ($updatePermissionDTO->name !== null) {
            $attributes['name'] = $updatePermissionDTO->name;
        }

        /** @var Permission $permission */
        $permission = $this->permissionRepository->update($updatePermissionDTO->id, $attributes);

        return $permission;
    }
}
