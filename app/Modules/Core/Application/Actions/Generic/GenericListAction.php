<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Generic List Action that works with any repository.
 *
 * @template TModel of Model
 */
final readonly class GenericListAction
{
    /**
     * @param  Repository<TModel>  $repository
     */
    public function __construct(
        private Repository $repository,
        private int $defaultPerPage = 15
    ) {}

    /**
     * Execute the list action with pagination.
     *
     * @return array{items: Collection<int, TModel>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function execute(int $page = 1, ?int $perPage = null): array
    {
        $perPage ??= $this->defaultPerPage;

        /** @var array{items: Collection<int, TModel>, total: int, page: int, perPage: int, totalPages: int} $result */
        $result = $this->repository->paginate($page, $perPage);

        return $result;
    }

    /**
     * Execute the list action with equality filters applied.
     *
     * Delegates to the repository's `paginateBy()` (provided by EloquentRepository).
     * A repository without it cannot filter, so this throws rather than silently
     * returning an unfiltered page.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: Collection<int, TModel>, total: int, page: int, perPage: int}
     *
     * @throws LogicException if the repository cannot apply filters
     */
    public function executeWithFilters(array $filters, int $page = 1, ?int $perPage = null): array
    {
        if ($filters === []) {
            return $this->execute($page, $perPage);
        }

        if (! method_exists($this->repository, 'paginateBy')) {
            throw new LogicException(sprintf(
                '%s cannot apply filters: implement paginateBy(array $criteria, int $page, int $perPage) '
                .'on it (EloquentRepository already provides one), or call execute() when no filters are needed.',
                $this->repository::class
            ));
        }

        /** @var array{items: Collection<int, TModel>, total: int, page: int, perPage: int} $result */
        $result = $this->repository->paginateBy($filters, $page, $perPage ?? $this->defaultPerPage);

        return $result;
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
     * @param  array<int, string>  $relations
     * @return array{items: Collection<int, TModel>, total: int, page: int, perPage: int}
     */
    public function executeWith(array $relations, int $page = 1, ?int $perPage = null): array
    {
        $result = $this->execute($page, $perPage);

        // Load relations on the items
        /** @var Collection<int, TModel>|list<TModel> $items */
        $items = $result['items'];
        if (is_array($items)) {
            $items = new Collection($items);
        }

        /** @phpstan-ignore-next-line */
        $items->load($relations);
        $result['items'] = $items;

        return $result;
    }
}
