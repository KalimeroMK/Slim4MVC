<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Models\Role;
use App\Repositories\RoleRepository;

final class GetRoleAction
{
    public function __construct(
        private readonly RoleRepository $repository
    ) {}

    /**
     * Execute getting a role by ID.
     *
     * @param int $id
     * @return Role
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): Role
    {
        $role = $this->repository->findOrFail($id);
        $role->load('permissions');

        return $role;
    }
}
