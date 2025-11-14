<?php

declare(strict_types=1);

namespace App\Actions\Permission;

final class ListPermissionAction
{
    public function __construct(
        private readonly PermissionRepository $repository
    ) {}

    /**
     * Execute listing permissions with pagination.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->repository->paginateWithRoles($page, $perPage);
    }
}
