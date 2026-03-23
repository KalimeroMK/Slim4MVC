<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\Actions;

use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;

final readonly class ListPermissionAction
{
    public function __construct(
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * Execute listing permissions with pagination.
     *
     * @return array{items: list<\App\Modules\Permission\Infrastructure\Models\Permission>, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->permissionRepository->paginateWithRoles($page, $perPage);
    }
}
