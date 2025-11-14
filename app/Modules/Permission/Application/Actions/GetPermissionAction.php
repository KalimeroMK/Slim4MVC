<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Modules\Permission\Infrastructure\Models\Permission;

final class GetPermissionAction
{
    public function __construct(
        private readonly PermissionRepository $repository
    ) {}

    /**
     * Execute getting a permission by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): Permission
    {
        $permission = $this->repository->findOrFail($id);
        $permission->load('roles');

        return $permission;
    }
}
