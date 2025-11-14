<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Repositories\PermissionRepository;

final class DeletePermissionAction
{
    public function __construct(
        private readonly PermissionRepository $repository
    ) {}

    /**
     * Execute permission deletion.
     *
     * @param int $id
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
