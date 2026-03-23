<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Trait to add API query filtering to Eloquent models.
 *
 * @template TModel of Model
 */
trait Filterable
{
    /**
     * Fields allowed for filtering.
     *
     * @var array<int, string>
     */
    protected array $filterable = [];

    /**
     * Fields allowed for sorting.
     *
     * @var array<int, string>
     */
    protected array $sortable = [];

    /**
     * Fields allowed for searching.
     *
     * @var array<int, string>
     */
    protected array $searchable = [];

    /**
     * Default sort order.
     *
     * @var array<string, string>
     */
    protected array $defaultSort = ['id' => 'desc'];

    /**
     * Allowed relationships for eager loading.
     *
     * @var array<int, string>
     */
    protected array $allowableIncludes = [];

    /**
     * Scope to apply API query filters.
     *
     * @param Builder<TModel> $builder
     * @return Builder<TModel>
     */
    public function scopeFilter(Builder $builder, Request $request): Builder
    {
        $config = [
            'filterable' => $this->getFilterableFields(),
            'sortable' => $this->getSortableFields(),
            'searchable' => $this->getSearchableFields(),
            'default_sort' => $this->getDefaultSort(),
        ];

        $queryBuilder = new QueryBuilder($request, $config);

        return $queryBuilder->apply($builder);
    }

    /**
     * Scope to get paginated results with filters.
     *
     * @param Builder<TModel> $builder
     * @return array{items: mixed, pagination: array<string, mixed>}
     */
    public function scopeFilterPaginate(Builder $builder, Request $request): array
    {
        $config = [
            'filterable' => $this->getFilterableFields(),
            'sortable' => $this->getSortableFields(),
            'searchable' => $this->getSearchableFields(),
            'default_sort' => $this->getDefaultSort(),
        ];

        $queryBuilder = new QueryBuilder($request, $config);

        return $queryBuilder->paginate($builder);
    }

    /**
     * Scope to get all results with filters.
     *
     * @param Builder<TModel> $builder
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function scopeFilterGet(Builder $builder, Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $config = [
            'filterable' => $this->getFilterableFields(),
            'sortable' => $this->getSortableFields(),
            'searchable' => $this->getSearchableFields(),
            'default_sort' => $this->getDefaultSort(),
        ];

        $queryBuilder = new QueryBuilder($request, $config);

        return $queryBuilder->get($builder);
    }

    /**
     * Get filterable fields.
     *
     * @return array<int, string>
     */
    protected function getFilterableFields(): array
    {
        return property_exists($this, 'filterable') ? $this->filterable : [];
    }

    /**
     * Get sortable fields.
     *
     * @return array<int, string>
     */
    protected function getSortableFields(): array
    {
        return property_exists($this, 'sortable') ? $this->sortable : [];
    }

    /**
     * Get searchable fields.
     *
     * @return array<int, string>
     */
    protected function getSearchableFields(): array
    {
        return property_exists($this, 'searchable') ? $this->searchable : [];
    }

    /**
     * Get default sort.
     *
     * @return array<string, string>
     */
    protected function getDefaultSort(): array
    {
        return property_exists($this, 'defaultSort') ? $this->defaultSort : ['id' => 'desc'];
    }
}
