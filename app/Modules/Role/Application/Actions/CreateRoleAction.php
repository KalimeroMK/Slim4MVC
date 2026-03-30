<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Application\DTOs\CreateRoleDTO;
use App\Modules\Role\Application\Interfaces\CreateRoleActionInterface;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final readonly class CreateRoleAction implements CreateRoleActionInterface
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * Execute role creation.
     */
    public function execute(CreateRoleDTO $createRoleDTO): Role
    {
        $role = $this->roleRepository->create([
            'name' => $createRoleDTO->name,
        ]);

        if ($createRoleDTO->permissions !== []) {
            $role->givePermissionTo($createRoleDTO->permissions);
        }

        return $role->load('permissions');
    }
}
