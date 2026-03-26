<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Repositories\Repository;

/**
 * Generic Delete Action that works with any repository.
 *
 * @template TModel of Model
 */
final class GenericDeleteAction
{
    /**
     * @param Repository<TModel> $repository
     */
    public function __construct(
        private readonly Repository $repository
    ) {}

    /**
     * Execute the delete action.
     *
     * @param int|string $id
     *
     * @throws NotFoundException
     */
    public function execute(int|string $id): void
    {
        $model = $this->repository->find($id);

        if ($model === null) {
            throw new NotFoundException('Resource not found');
        }

        $this->repository->delete($id);
    }
}
