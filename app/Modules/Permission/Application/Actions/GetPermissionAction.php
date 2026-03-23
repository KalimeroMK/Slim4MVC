<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Actions;

use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;

final readonly class GetPermissionAction
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * Execute getting a permission by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): Permission
    {
        /** @var Permission $permission */
        $permission = $this->permissionRepository->findOrFail($id);
        $permission->load('roles');

        return $permission;
    }
}
