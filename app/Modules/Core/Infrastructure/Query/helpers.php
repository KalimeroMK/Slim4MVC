<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Query\QueryBuilder;
use App\Modules\Core\Infrastructure\Query\QueryParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ServerRequestInterface as Request;

if (! function_exists('query_parser')) {
    /**
     * Create a new QueryParser instance.
     */
    function query_parser(Request $request): QueryParser
    {
        return new QueryParser($request);
    }
}

if (! function_exists('query_builder')) {
    /**
     * Create a new QueryBuilder instance.
     *
     * @param  array<string, mixed>  $config
     *
     * @phpstan-ignore missingType.generics
     */
    function query_builder(Request $request, ?array $config = null): QueryBuilder
    {
        /** @phpstan-ignore-next-line */
        return new QueryBuilder($request, $config);
    }
}

if (! function_exists('query_filter')) {
    /**
     * Apply filters to an Eloquent query.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>|Builder<TModel>  $model
     * @param  array<string, mixed>  $config
     * @return Builder<TModel>
     */
    function query_filter(string|Builder $model, Request $request, ?array $config = null): Builder
    {
        $builder = new QueryBuilder($request, $config);

        if (is_string($model)) {
            /** @phpstan-ignore-next-line */
            return $builder->apply($model::query());
        }

        /** @phpstan-ignore-next-line */
        return $builder->apply($model);
    }
}

if (! function_exists('query_paginate')) {
    /**
     * Get paginated results with filters.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>|Builder<TModel>  $model
     * @param  array<string, mixed>  $config
     * @return array{items: mixed, pagination: array<string, mixed>}
     */
    function query_paginate(string|Builder $model, Request $request, ?array $config = null): array
    {
        $builder = new QueryBuilder($request, $config);

        return $builder->paginate($model);
    }
}
