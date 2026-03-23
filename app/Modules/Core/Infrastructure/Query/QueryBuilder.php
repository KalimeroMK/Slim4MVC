<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Build Eloquent queries from API query parameters.
 *
 * @template TModel of Model
 */
final readonly class QueryBuilder
{
    private QueryParser $queryParser;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(
        Request $request,
        ?array $config = null
    ) {
        $this->queryParser = new QueryParser($request);
        $this->config = $config ?? [
            'searchable' => [],
            'filterable' => [],
            'sortable' => [],
        ];
    }

    /**
     * Apply query parameters to Eloquent builder.
     *
     * @param Builder<TModel> $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        // Apply eager loading first
        $this->applyIncludes($builder);

        // Apply field selection
        $this->applyFields($builder);

        // Apply filters
        $this->applyFilters($builder);

        // Apply ranges
        $this->applyRanges($builder);

        // Apply search
        $this->applySearch($builder);

        // Apply sorting
        $this->applySorts($builder);

        return $builder;
    }

    /**
     * Build complete query with pagination.
     *
     * @param  class-string<TModel>|Builder<TModel>  $model
     * @return array{items: mixed, pagination: array<string, mixed>}
     */
    public function paginate(string|Builder $model): array
    {
        $query = is_string($model) ? $model::query() : $model;

        $this->apply($query);

        $perPage = $this->queryParser->perPage();
        $page = max(1, (int) ($this->queryParser->raw()['page'] ?? 1));

        $lengthAwarePaginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $lengthAwarePaginator->items(),
            'pagination' => [
                'total' => $lengthAwarePaginator->total(),
                'per_page' => $lengthAwarePaginator->perPage(),
                'current_page' => $lengthAwarePaginator->currentPage(),
                'last_page' => $lengthAwarePaginator->lastPage(),
                'from' => $lengthAwarePaginator->firstItem() ?? 0,
                'to' => $lengthAwarePaginator->lastItem() ?? 0,
            ],
        ];
    }

    /**
     * Get all results without pagination.
     *
     * @param  class-string<TModel>|Builder<TModel>  $model
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function get(string|Builder $model): \Illuminate\Database\Eloquent\Collection
    {
        $query = is_string($model) ? $model::query() : $model;

        $this->apply($query);

        return $query->get();
    }

    /**
     * Get first result.
     *
     * @param  class-string<TModel>|Builder<TModel>  $model
     * @return TModel|null
     */
    public function first(string|Builder $model): ?Model
    {
        $query = is_string($model) ? $model::query() : $model;

        $this->apply($query);

        return $query->first();
    }

    /**
     * Count results.
     *
     * @param  class-string<TModel>|Builder<TModel>  $model
     */
    public function count(string|Builder $model): int
    {
        $query = is_string($model) ? $model::query() : $model;

        $this->applyFilters($query);
        $this->applyRanges($query);
        $this->applySearch($query);

        return $query->count();
    }

    /**
     * Apply eager loading.
     *
     * @param Builder<TModel> $builder
     */
    private function applyIncludes(Builder $builder): void
    {
        $includes = $this->queryParser->includes();

        if ($includes !== []) {
            $builder->with($includes);
        }
    }

    /**
     * Apply field selection.
     *
     * @param Builder<TModel> $builder
     */
    private function applyFields(Builder $builder): void
    {
        $fields = $this->queryParser->fields();

        if ($fields !== null && $fields !== []) {
            $builder->select($fields);
        }
    }

    /**
     * Apply filters.
     *
     * @param Builder<TModel> $builder
     */
    private function applyFilters(Builder $builder): void
    {
        $filters = $this->queryParser->filters();

        foreach ($filters as $field => $value) {
            // Skip if field is not in allowed filterable list (when configured)
            if ($this->config['filterable'] !== [] && ! in_array($field, $this->config['filterable'], true)) {
                continue;
            }

            $this->applyFilter($builder, $field, $value);
        }
    }

    /**
     * Apply single filter.
     *
     * @param Builder<TModel> $builder
     */
    private function applyFilter(Builder $builder, string $field, mixed $value): void
    {
        // Handle array values (IN operator)
        if (is_array($value)) {
            $builder->whereIn($field, $value);

            return;
        }

        // Handle operators
        if (is_string($value) && str_contains($value, ':')) {
            [$operator, $filterValue] = explode(':', $value, 2);
            $filterValue = $this->castFilterValue($filterValue);

            match ($operator) {
                'eq' => $builder->where($field, '=', $filterValue),
                'ne', 'neq' => $builder->where($field, '!=', $filterValue),
                'gt' => $builder->where($field, '>', $filterValue),
                'gte' => $builder->where($field, '>=', $filterValue),
                'lt' => $builder->where($field, '<', $filterValue),
                'lte' => $builder->where($field, '<=', $filterValue),
                'like' => $builder->where($field, 'like', '%'.$filterValue.'%'),
                'starts' => $builder->where($field, 'like', $filterValue.'%'),
                'ends' => $builder->where($field, 'like', '%'.$filterValue),
                'in' => $builder->whereIn($field, explode(',', (string) $filterValue)),
                'nin', 'not_in' => $builder->whereNotIn($field, explode(',', (string) $filterValue)),
                'null' => $filterValue === 'true' || $filterValue === true
                    ? $builder->whereNull($field)
                    : $builder->whereNotNull($field),
                default => $builder->where($field, '=', $value),
            };
        } else {
            // Simple equality
            $builder->where($field, '=', $this->castFilterValue($value));
        }
    }

    /**
     * Apply range filters.
     *
     * @param Builder<TModel> $builder
     */
    private function applyRanges(Builder $builder): void
    {
        $ranges = $this->queryParser->ranges();

        foreach ($ranges as $field => $range) {
            if ($range['min'] !== null && $range['min'] !== '') {
                $builder->where($field, '>=', $range['min']);
            }

            if ($range['max'] !== null && $range['max'] !== '') {
                $builder->where($field, '<=', $range['max']);
            }
        }
    }

    /**
     * Apply search.
     *
     * @param Builder<TModel> $builder
     */
    private function applySearch(Builder $builder): void
    {
        $search = $this->queryParser->search();

        if ($search === null) {
            return;
        }

        $searchable = $this->config['searchable'] ?? [];

        if ($searchable === []) {
            return;
        }

        $builder->where(function (Builder $builder) use ($search, $searchable): void {
            foreach ($searchable as $field) {
                $builder->orWhere($field, 'like', '%'.$search.'%');
            }
        });
    }

    /**
     * Apply sorting.
     *
     * @param Builder<TModel> $builder
     */
    private function applySorts(Builder $builder): void
    {
        $sorts = $this->queryParser->sorts();

        // Default sort if none provided
        if ($sorts === [] && isset($this->config['default_sort'])) {
            $sorts = $this->config['default_sort'];
        }

        foreach ($sorts as $field => $direction) {
            // Skip if field is not in allowed sortable list (when configured)
            if ($this->config['sortable'] !== [] && ! in_array($field, $this->config['sortable'], true)) {
                continue;
            }

            $builder->orderBy($field, $direction);
        }
    }

    /**
     * Cast filter value to appropriate type.
     */
    private function castFilterValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        $lower = strtolower($value);
        if ($lower === 'true') {
            return true;
        }

        if ($lower === 'false') {
            return false;
        }

        if ($lower === 'null') {
            return null;
        }

        return $value;
    }
}
