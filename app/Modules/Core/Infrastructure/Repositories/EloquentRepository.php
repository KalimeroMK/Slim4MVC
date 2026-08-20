<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Base Eloquent repository implementation.
 *
 * @template TModel of Model
 *
 * @implements Repository<TModel>
 */
abstract class EloquentRepository implements Repository
{
    /**
     * A bare column reference, optionally table-qualified.
     *
     * Criteria keys can originate from HTTP input, so they are validated before
     * being used as column names rather than trusted.
     */
    private const string COLUMN_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?$/';

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
    final public function all(): Collection
    {
        return $this->model()::all();
    }

    /**
     * Find a record by ID.
     *
     * @return TModel|null
     */
    final public function find(int $id): ?Model
    {
        $modelClass = $this->model();

        /** @phpstan-ignore-next-line */
        return $modelClass::find($id);
    }

    /**
     * Find a record by ID or throw exception.
     *
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    final public function findOrFail(int $id): Model
    {
        $modelClass = $this->model();

        /** @phpstan-ignore-next-line */
        return $modelClass::findOrFail($id);
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    final public function create(array $attributes): Model
    {
        $modelClass = $this->model();

        /** @phpstan-ignore-next-line */
        return $modelClass::create($attributes);
    }

    /**
     * Update a record.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    final public function update(int $id, array $attributes): Model
    {
        /** @var TModel $model */
        $model = $this->findOrFail($id);
        $model->update($attributes);

        $fresh = $model->fresh();

        return $fresh ?? $model;
    }

    /**
     * Delete a record.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    final public function delete(int $id): bool
    {
        /** @var TModel $model */
        $model = $this->findOrFail($id);

        return $model->delete() ?? false;
    }

    /**
     * Get paginated records.
     *
     * @return array{items: list<TModel>, total: int, page: int, perPage: int}
     */
    final public function paginate(int $page = 1, int $perPage = 15): array
    {
        $modelClass = $this->model();

        /** @phpstan-ignore-next-line */
        $lengthAwarePaginator = $modelClass::query()
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        /** @var list<TModel> $items */
        $items = $lengthAwarePaginator->items();

        return [
            'items' => $items,
            'total' => $lengthAwarePaginator->total(),
            'page' => $lengthAwarePaginator->currentPage(),
            'perPage' => $lengthAwarePaginator->perPage(),
        ];
    }

    /**
     * Find records by criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, TModel>
     */
    final public function findBy(array $criteria): Collection
    {
        $query = $this->model()::query();

        foreach ($criteria as $field => $value) {
            $query->where($this->assertColumn($field), $value);
        }

        return $query->get();
    }

    /**
     * Get paginated records matching the given criteria.
     *
     * Each criterion is an equality match, or an `IN` match when the value is a list.
     *
     * @param  array<string, mixed>  $criteria
     * @return array{items: list<TModel>, total: int, page: int, perPage: int}
     *
     * @throws InvalidArgumentException if a criterion key is not a plain column name
     */
    final public function paginateBy(array $criteria, int $page = 1, int $perPage = 15): array
    {
        $modelClass = $this->model();

        /** @phpstan-ignore-next-line */
        $query = $modelClass::query();

        foreach ($criteria as $field => $value) {
            $column = $this->assertColumn($field);

            if (is_array($value)) {
                $query->whereIn($column, $value);

                continue;
            }

            $query->where($column, '=', $value);
        }

        $lengthAwarePaginator = $query
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        /** @var list<TModel> $items */
        $items = $lengthAwarePaginator->items();

        return [
            'items' => $items,
            'total' => $lengthAwarePaginator->total(),
            'page' => $lengthAwarePaginator->currentPage(),
            'perPage' => $lengthAwarePaginator->perPage(),
        ];
    }

    /**
     * @throws InvalidArgumentException if the name is not a plain column reference
     */
    private function assertColumn(int|string $field): string
    {
        $field = (string) $field;

        if (preg_match(self::COLUMN_PATTERN, $field) !== 1) {
            throw new InvalidArgumentException(sprintf('%s is not a valid column name.', $field));
        }

        return $field;
    }
}
