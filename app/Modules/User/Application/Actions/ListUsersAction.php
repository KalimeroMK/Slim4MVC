<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class ListUsersAction
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute listing users with pagination.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->repository->paginateWithRoles($page, $perPage);
    }
}
