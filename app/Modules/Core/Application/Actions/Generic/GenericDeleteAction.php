<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic Delete Action that works with any repository.
 *
 * @template TModel of Model
 */
final readonly class GenericDeleteAction
{
    /**
     * @param Repository<TModel> $repository
     */
    public function __construct(
        private Repository $repository
    ) {}

    /**
     * Execute the delete action.
     *
     *
     * @throws NotFoundException
     */
    public function execute(int|string $id): void
    {
        $model = $this->repository->find((int) $id);

        if (!$model instanceof \Illuminate\Database\Eloquent\Model) {
            throw new NotFoundException('Resource not found');
        }

        $this->repository->delete((int) $id);
    }
}
