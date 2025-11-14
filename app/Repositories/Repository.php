<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository interface for data access layer.
 *
 * @template TModel of Model
 */
interface Repository
{
    /**
     * Get all records.
     *
     * @return Collection<int, TModel>
     */
    public function all(): Collection;

    /**
     * Find a record by ID.
     *
     * @return TModel|null
     */
    public function find(int $id): ?Model;

    /**
     * Find a record by ID or throw exception.
     *
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * Update a record.
     *
     * @param int $id
     * @param array<string, mixed> $attributes
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $attributes): Model;

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool;

    /**
     * Get paginated records.
     *
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function paginate(int $page = 1, int $perPage = 15): array;

    /**
     * Find records by criteria.
     *
     * @param array<string, mixed> $criteria
     * @return Collection<int, TModel>
     */
    public function findBy(array $criteria): Collection;
}

