<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class GetUserAction
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute getting a user by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): User
    {
        $user = $this->repository->findOrFail($id);
        $user->load('roles');

        return $user;
    }
}
