<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\Actions;

use App\Modules\Role\Infrastructure\Repositories\RoleRepository;

final readonly class ListRolesAction
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * Execute listing roles with pagination.
     *
     * @return array{items: list<\App\Modules\Role\Infrastructure\Models\Role>, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->roleRepository->paginateWithPermissions($page, $perPage);
    }
}
