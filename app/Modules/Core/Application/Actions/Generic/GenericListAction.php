<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Generic List Action that works with any repository.
 *
 * @template TModel of Model
 */
final class GenericListAction
{
    /**
     * @param Repository<TModel> $repository
     */
    public function __construct(
        private readonly Repository $repository,
        private readonly int $defaultPerPage = 15
    ) {}

    /**
     * Execute the list action with pagination.
     *
     * @param int $page
     * @param int|null $perPage
     * @return array{items: Collection<int, TModel>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function execute(int $page = 1, ?int $perPage = null): array
    {
        $perPage = $perPage ?? $this->defaultPerPage;

        return $this->repository->paginate($page, $perPage);
    }

    /**
     * Execute with filters.
     *
     * Note: This implementation ignores filters.
     * For filtering support, implement paginateWithFilters() in your repository.
     *
     * @param array<string, mixed> $filters
     * @param int $page
     * @param int|null $perPage
     * @return array{items: Collection<int, TModel>, total: int, page: int, perPage: int}
     */
    public function executeWithFilters(array $filters, int $page = 1, ?int $perPage = null): array
    {
        // Suppress unused variable warning
        unset($filters);
        
        return $this->execute($page, $perPage);
    }

    /**
     * Get all records without pagination.
     *
     * @return Collection<int, TModel>
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Execute with eager loading.
     *
     * Note: This implementation loads relations after pagination.
     * For better performance, implement paginateWithRelations() in your repository.
     *
     * @param array<int, string> $relations
     * @param int $page
     * @param int|null $perPage
     * @return array{items: Collection<int, TModel>, total: int, page: int, perPage: int}
     */
    public function executeWith(array $relations, int $page = 1, ?int $perPage = null): array
    {
        $result = $this->execute($page, $perPage);
        
        // Load relations on the collection
        $result['items']->load($relations);
        
        return $result;
    }
}
