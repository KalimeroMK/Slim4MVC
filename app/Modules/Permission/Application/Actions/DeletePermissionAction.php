<?php

declare(strict_types=1);

namespace App\Actions\Permission;

final class DeletePermissionAction
{
    public function __construct(
        private readonly PermissionRepository $repository
    ) {}

    /**
     * Execute permission deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
