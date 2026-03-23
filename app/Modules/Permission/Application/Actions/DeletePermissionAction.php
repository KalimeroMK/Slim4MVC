<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Actions;

use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;

final readonly class DeletePermissionAction
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * Execute permission deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): void
    {
        $this->permissionRepository->delete($id);
    }
}
