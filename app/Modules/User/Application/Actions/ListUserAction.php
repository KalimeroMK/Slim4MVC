<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class ListUserAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Execute listing Users with pagination.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->userRepository->paginate($page, $perPage);
    }
}
