<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final class GetRoleAction
{
    public function __construct(
        private readonly RoleRepository $repository
    ) {}

    /**
     * Execute getting a role by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): Role
    {
        $role = $this->repository->findOrFail($id);
        $role->load('permissions');

        return $role;
    }
}
