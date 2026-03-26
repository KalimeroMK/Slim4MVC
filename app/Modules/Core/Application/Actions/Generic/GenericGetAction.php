<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic Get Action that works with any repository.
 *
 * @template TModel of Model
 */
final class GenericGetAction
{
    /**
     * @param Repository<TModel> $repository
     */
    public function __construct(
        private readonly Repository $repository
    ) {}

    /**
     * Execute the get action.
     *
     * @param int|string $id
     * @return TModel
     *
     * @throws NotFoundException
     */
    public function execute(int|string $id): Model
    {
        /** @var TModel|null $model */
        $model = $this->repository->find((int) $id);

        if ($model === null) {
            throw new NotFoundException('Resource not found');
        }

        return $model;
    }

    /**
     * Execute with eager loading of relations.
     *
     * Note: This implementation loads relations after finding the model.
     * For better performance, consider implementing findWith() in your repository.
     *
     * @param int|string $id
     * @param array<int, string> $relations
     * @return TModel
     *
     * @throws NotFoundException
     */
    public function executeWith(int|string $id, array $relations): Model
    {
        $model = $this->execute($id);
        
        // Load relations if the model supports it
        /** @phpstan-ignore-next-line */
        $model->load($relations);

        return $model;
    }
}
