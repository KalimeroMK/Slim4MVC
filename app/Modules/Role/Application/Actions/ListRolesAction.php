<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final class ListRolesAction
{
    public function __construct(
        private readonly RoleRepository $repository
    ) {}

    /**
     * Execute listing roles with pagination.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->repository->paginateWithPermissions($page, $perPage);
    }
}
