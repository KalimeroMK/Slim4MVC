<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final readonly class DeleteRoleAction
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * Execute role deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): void
    {
        $this->roleRepository->delete($id);
    }
}
