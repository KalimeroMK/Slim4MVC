<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\DTO\Permission\UpdatePermissionDTO;
use App\Interface\Permission\UpdatePermissionActionInterface;
use App\Models\Permission;
use App\Repositories\PermissionRepository;

final class UpdatePermissionAction implements UpdatePermissionActionInterface
{
    public function __construct(
        private readonly PermissionRepository $repository
    ) {}

    /**
     * Execute permission update.
     */
    public function execute(UpdatePermissionDTO $dto): Permission
    {
        $attributes = [];
        if ($dto->name !== null) {
            $attributes['name'] = $dto->name;
        }

        /** @var Permission $permission */
        $permission = $this->repository->update($dto->id, $attributes);

        return $permission;
    }
}
