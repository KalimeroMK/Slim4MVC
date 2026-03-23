<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Cache;

use App\Modules\Core\Infrastructure\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Example of cached repository implementation.
 *
 * This trait can be used in repositories to add caching functionality.
 * It wraps the base EloquentRepository methods with cache operations.
 *
 * @template TModel of Model
 */
trait RepositoryCacheExample
{
    use Cacheable;

    /**
     * Cache TTL for repository queries in seconds.
     * Override this in your repository to customize.
     */
    protected int $repositoryCacheTtl = 300; // 5 minutes default

    /**
     * Find a record by ID with caching.
     *
     * @return TModel|null
     */
    public function findCached(int $id): ?Model
    {
        $this->setCacheTtl($this->repositoryCacheTtl);

        return $this->remember('find:'.$id, fn () => $this->find($id));
    }

    /**
     * Find a record by ID or throw exception with caching.
     *
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailCached(int $id): Model
    {
        $this->setCacheTtl($this->repositoryCacheTtl);

        return $this->remember('findOrFail:'.$id, fn () => $this->findOrFail($id));
    }

    /**
     * Get all records with caching.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function allCached(): \Illuminate\Database\Eloquent\Collection
    {
        $this->setCacheTtl($this->repositoryCacheTtl);

        return $this->remember('all', fn () => $this->all());
    }

    /**
     * Get paginated records with caching.
     *
     * @return array{items: list<TModel>, total: int, page: int, perPage: int}
     */
    public function paginateCached(int $page = 1, int $perPage = 15): array
    {
        $this->setCacheTtl($this->repositoryCacheTtl);

        return $this->remember(sprintf('paginate:%d:%d', $page, $perPage), fn () => $this->paginate($page, $perPage));
    }

    /**
     * Find records by criteria with caching.
     *
     * @param  array<string, mixed>  $criteria
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function findByCached(array $criteria): \Illuminate\Database\Eloquent\Collection
    {
        $this->setCacheTtl($this->repositoryCacheTtl);

        $cacheKey = 'findBy:'.md5(serialize($criteria));

        return $this->remember($cacheKey, fn () => $this->findBy($criteria));
    }

    /**
     * Override create to clear cache after creation.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function createAndClearCache(array $attributes): Model
    {
        $result = parent::create($attributes);
        $this->clearModelCache();

        return $result;
    }

    /**
     * Override update to clear cache after update.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateAndClearCache(int $id, array $attributes): Model
    {
        $result = parent::update($id, $attributes);
        $this->clearModelCache();

        return $result;
    }

    /**
     * Override delete to clear cache after deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteAndClearCache(int $id): bool
    {
        $result = parent::delete($id);
        $this->clearModelCache();

        return $result;
    }

    /**
     * Clear all cache for this model.
     */
    public function clearModelCache(): bool
    {
        // Clear specific keys
        $this->cacheDelete('all');
        $this->cacheFlush();

        return true;
    }
}

/**
 * Example UserRepository with caching.
 *
 * Usage in your repository:
 *
 * ```php
 * class UserRepository extends EloquentRepository
 * {
 *     use RepositoryCacheExample;
 *
 *     protected function model(): string
 *     {
 *         return User::class;
 *     }
 *
 *     // Cache tag for this repository
 *     protected string $cacheTag = 'users';
 *
 *     // Custom cached method
 *     public function findByEmailCached(string $email): ?User
 *     {
 *         return $this->remember("email:{$email}", function () use ($email) {
 *             return $this->findByEmail($email);
 *         });
 *     }
 * }
 * ```
 */
