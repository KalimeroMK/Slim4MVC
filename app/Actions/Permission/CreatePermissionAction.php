<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\DTO\Permission\CreatePermissionDTO;
use App\Interface\Permission\CreatePermissionActionInterface;
use App\Models\Permission;
use App\Repositories\PermissionRepository;

final class CreatePermissionAction implements CreatePermissionActionInterface
{
    public function __construct(
        private readonly PermissionRepository $repository
    ) {}

    /**
     * Execute permission creation.
     *
     * @param CreatePermissionDTO $dto
     * @return Permission
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
