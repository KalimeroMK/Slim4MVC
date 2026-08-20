<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Build Eloquent queries from API query parameters.
 *
 * Security model — allowlists are fail-closed. Column and relation names arriving
 * from the query string are never passed to the query builder unless they appear in
 * the matching config key, and they must also look like a plain SQL identifier.
 * A missing or empty allowlist means "nothing is allowed", not "everything".
 *
 * Recognised config keys, each a list of names:
 *   - `filterable`  → `?filter[field]=` and `?range[field]=`
 *   - `sortable`    → `?sort=field,-other`
 *   - `searchable`  → columns scanned by `?search=`
 *   - `includable`  → relations loadable via `?include=`
 *   - `selectable`  → columns selectable via `?fields=`
 *   - `default_sort` → `['field' => 'asc']` applied when no `?sort=` is given
 *
 * Example:
 * ```php
 * $result = query_paginate(User::class, $request, [
 *     'filterable' => ['name', 'email', 'created_at'],
 *     'sortable'   => ['name', 'created_at'],
 *     'searchable' => ['name', 'email'],
 *     'includable' => ['roles'],
 * ]);
 * ```
 *
 * @template TModel of Model
 */
final readonly class QueryBuilder
{
    /**
     * A bare column reference, optionally table-qualified: `name`, `users.name`.
     */
    private const string COLUMN_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?$/';

    /**
     * A relation path, possibly nested: `roles`, `roles.permissions`.
     */
    private const string RELATION_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*$/';

    private const array SORT_DIRECTIONS = ['asc', 'desc'];

    private QueryParser $queryParser;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(
        Request $request,
        ?array $config = null
    ) {
        $this->queryParser = new QueryParser($request);
        $this->config = $config ?? [];
    }

    /**
     * Apply query parameters to Eloquent builder.
     *
     * @param  Builder<TModel>  $builder
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
        $page = $this->queryParser->pagination()['page'];

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
     * @return Collection<int, TModel>
     */
    public function get(string|Builder $model): Collection
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
     * Read an allowlist from the config.
     *
     * @return list<string>
     */
    private function allowList(string $key): array
    {
        $allowed = $this->config[$key] ?? [];

        if (! is_array($allowed)) {
            return [];
        }

        return array_values(array_filter($allowed, 'is_string'));
    }

    /**
     * Whether a user-supplied name is in the given allowlist and is a safe identifier.
     *
     * Fail-closed: an empty allowlist permits nothing.
     */
    private function isAllowed(string $key, string $name, string $pattern = self::COLUMN_PATTERN): bool
    {
        return in_array($name, $this->allowList($key), true)
            && preg_match($pattern, $name) === 1;
    }

    /**
     * Apply eager loading. Relations must be listed in `includable`.
     *
     * @param  Builder<TModel>  $builder
     */
    private function applyIncludes(Builder $builder): void
    {
        $includes = [];

        foreach ($this->queryParser->includes() as $relation) {
            if ($this->isAllowed('includable', $relation, self::RELATION_PATTERN)) {
                $includes[] = $relation;
            }
        }

        if ($includes !== []) {
            $builder->with($includes);
        }
    }

    /**
     * Apply field selection. Columns must be listed in `selectable`.
     *
     * @param  Builder<TModel>  $builder
     */
    private function applyFields(Builder $builder): void
    {
        $requested = $this->queryParser->fields();

        if ($requested === null) {
            return;
        }

        $fields = [];
        foreach ($requested as $field) {
            if ($this->isAllowed('selectable', $field)) {
                $fields[] = $field;
            }
        }

        if ($fields !== []) {
            $builder->select($fields);
        }
    }

    /**
     * Apply filters. Columns must be listed in `filterable`.
     *
     * @param  Builder<TModel>  $builder
     */
    private function applyFilters(Builder $builder): void
    {
        foreach ($this->queryParser->filters() as $field => $value) {
            $field = (string) $field;

            if (! $this->isAllowed('filterable', $field)) {
                continue;
            }

            $this->applyFilter($builder, $field, $value);
        }
    }

    /**
     * Apply single filter.
     *
     * @param  Builder<TModel>  $builder
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
     * Apply range filters. Columns must be listed in `filterable`.
     *
     * @param  Builder<TModel>  $builder
     */
    private function applyRanges(Builder $builder): void
    {
        foreach ($this->queryParser->ranges() as $field => $range) {
            $field = (string) $field;

            if (! $this->isAllowed('filterable', $field)) {
                continue;
            }

            if ($range['min'] !== null && $range['min'] !== '') {
                $builder->where($field, '>=', $range['min']);
            }

            if ($range['max'] !== null && $range['max'] !== '') {
                $builder->where($field, '<=', $range['max']);
            }
        }
    }

    /**
     * Apply search across the `searchable` columns.
     *
     * @param  Builder<TModel>  $builder
     */
    private function applySearch(Builder $builder): void
    {
        $search = $this->queryParser->search();

        if ($search === null) {
            return;
        }

        $searchable = [];
        foreach ($this->allowList('searchable') as $field) {
            if (preg_match(self::COLUMN_PATTERN, $field) === 1) {
                $searchable[] = $field;
            }
        }

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
     * Apply sorting. Requested columns must be listed in `sortable`.
     *
     * `default_sort` is developer-supplied rather than user-supplied, so it is not
     * subject to the `sortable` allowlist — but it is still validated as an
     * identifier. It applies whenever no requested sort survived filtering, which
     * keeps pagination ordering deterministic.
     *
     * @param  Builder<TModel>  $builder
     */
    private function applySorts(Builder $builder): void
    {
        $applied = 0;

        foreach ($this->queryParser->sorts() as $field => $direction) {
            $field = (string) $field;

            if (! $this->isAllowed('sortable', $field)) {
                continue;
            }

            $applied += $this->orderBy($builder, $field, $direction);
        }

        if ($applied > 0) {
            return;
        }

        $default = $this->config['default_sort'] ?? [];

        if (! is_array($default)) {
            return;
        }

        foreach ($default as $field => $direction) {
            $field = (string) $field;

            if (preg_match(self::COLUMN_PATTERN, $field) !== 1) {
                continue;
            }

            $this->orderBy($builder, $field, $direction);
        }
    }

    /**
     * Apply a validated `order by`. Returns 1 when applied, 0 when rejected.
     *
     * @param  Builder<TModel>  $builder
     */
    private function orderBy(Builder $builder, string $field, mixed $direction): int
    {
        $direction = is_string($direction) ? strtolower($direction) : '';

        if (! in_array($direction, self::SORT_DIRECTIONS, true)) {
            return 0;
        }

        $builder->orderBy($field, $direction);

        return 1;
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
