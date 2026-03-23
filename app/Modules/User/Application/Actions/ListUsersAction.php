<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class ListUsersAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Execute listing users with pagination.
     *
     * @return array{items: list<\App\Modules\User\Infrastructure\Models\User>, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->userRepository->paginateWithRoles($page, $perPage);
    }
}
