<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final readonly class GetRoleAction
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * Execute getting a role by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): Role
    {
        $role = $this->roleRepository->findOrFail($id);
        $role->load('permissions');

        return $role;
    }
}
