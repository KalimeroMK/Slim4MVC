<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Actions;

use App\Modules\Permission\Application\DTOs\CreatePermissionDTO;
use App\Modules\Permission\Application\Interfaces\CreatePermissionActionInterface;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;

final class CreatePermissionAction implements CreatePermissionActionInterface
{
    public function __construct(
        private readonly PermissionRepository $repository
    ) {}

    /**
     * Execute permission creation.
     */
    public function execute(CreatePermissionDTO $dto): Permission
    {
        /** @var Permission $permission */
        $permission = $this->repository->create([
            'name' => $dto->name,
        ]);

        return $permission;
    }
}
