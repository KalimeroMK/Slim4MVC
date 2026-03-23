<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class GetUserAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Execute getting a user by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): User
    {
        $user = $this->userRepository->findOrFail($id);
        $user->load('roles');

        return $user;
    }
}
