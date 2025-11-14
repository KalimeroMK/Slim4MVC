<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Eloquent repository implementation.
 *
 * @template TModel of Model
 * @implements Repository<TModel>
 */
abstract class EloquentRepository implements Repository
{
    /**
     * Get the model class name.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * Get all records.
     *
     * @return Collection<int, TModel>
     */
    public function all(): Collection
    {
        return $this->model()::all();
    }

    /**
     * Find a record by ID.
     *
     * @return TModel|null
     */
    public function find(int $id): ?Model
    {
        return $this->model()::find($id);
    }

    /**
     * Find a record by ID or throw exception.
     *
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model
    {
        return $this->model()::findOrFail($id);
    }

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        return $this->model()::create($attributes);
    }

    /**
     * Update a record.
     *
     * @param int $id
     * @param array<string, mixed> $attributes
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $attributes): Model
    {
        $model = $this->findOrFail($id);
        $model->update($attributes);

        return $model->fresh();
    }

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    /**
     * Get paginated records.
     *
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $paginator = $this->model()::query()
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
        ];
    }

    /**
     * Find records by criteria.
     *
     * @param array<string, mixed> $criteria
     * @return Collection<int, TModel>
     */
    public function findBy(array $criteria): Collection
    {
        $query = $this->model()::query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->get();
    }
}

