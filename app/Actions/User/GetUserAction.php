<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Repositories\UserRepository;

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
