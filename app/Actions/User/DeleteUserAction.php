<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Repositories\UserRepository;

final class DeleteUserAction
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute user deletion.
     *
     * @param int $id
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
