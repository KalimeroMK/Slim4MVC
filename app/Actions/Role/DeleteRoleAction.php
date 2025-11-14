<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Repositories\RoleRepository;

final class DeleteRoleAction
{
    public function __construct(
        private readonly RoleRepository $repository
    ) {}

    /**
     * Execute role deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
