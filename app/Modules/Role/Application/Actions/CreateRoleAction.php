<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Application\DTOs\CreateRoleDTO;
use App\Modules\Role\Application\Interfaces\CreateRoleActionInterface;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

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
