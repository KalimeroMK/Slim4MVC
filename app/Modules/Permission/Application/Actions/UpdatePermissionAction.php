<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Actions;

use App\Modules\Permission\Application\DTOs\UpdatePermissionDTO;
use App\Modules\Permission\Application\Interfaces\UpdatePermissionActionInterface;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;

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
