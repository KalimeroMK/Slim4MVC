<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Actions;

use App\Modules\Permission\Application\DTOs\CreatePermissionDTO;
use App\Modules\Permission\Application\Interfaces\CreatePermissionActionInterface;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;

final readonly class CreatePermissionAction implements CreatePermissionActionInterface
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * Execute permission creation.
     */
    public function execute(CreatePermissionDTO $createPermissionDTO): Permission
    {
        /** @var Permission $permission */
        $permission = $this->permissionRepository->create([
            'name' => $createPermissionDTO->name,
        ]);

        return $permission;
    }
}
