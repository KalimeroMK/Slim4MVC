<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\DTO\Role\CreateRoleDTO;
use App\Interface\Role\CreateRoleActionInterface;
use App\Models\Role;
use App\Repositories\RoleRepository;

final class CreateRoleAction implements CreateRoleActionInterface
{
    public function __construct(
        private readonly RoleRepository $repository
    ) {}

    /**
     * Execute role creation.
     *
     * @return array<string, mixed>|null
     */
    public function execute(CreateRoleDTO $dto): ?array
    {
        $role = $this->repository->create([
            'name' => $dto->name,
        ]);

        if (! empty($dto->permissions)) {
            $role->givePermissionTo($dto->permissions);
        }

        return $role->load('permissions')->toArray();
    }
}
