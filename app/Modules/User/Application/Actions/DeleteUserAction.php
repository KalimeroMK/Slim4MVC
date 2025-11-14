<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class DeleteUserAction
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute user deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
