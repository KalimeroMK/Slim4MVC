<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Database\Eloquent\RelationPreloader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Preload relations on a model or collection.
 *
 * This is a helper function for RelationPreloader::load() and RelationPreloader::loadMany().
 *
 * @template TModel of Model
 *
 * @param TModel|Collection<int, TModel> $models
 * @param list<string>|string $relations
 * @return TModel|Collection<int, TModel>
 */
function preload(Model|Collection $models, array|string $relations): Model|Collection
{
    if ($models instanceof Collection) {
        return RelationPreloader::loadMany($models, $relations);
    }

    return RelationPreloader::load($models, $relations);
}

/**
 * Preload missing relations only.
 *
 * @template TModel of Model
 *
 * @param Collection<int, TModel> $models
 * @param list<string>|string $relations
 * @return Collection<int, TModel>
 */
function preload_missing(Collection $models, array|string $relations): Collection
{
    return RelationPreloader::loadMissing($models, $relations);
}

/**
 * Enable auto-eager-loading globally.
 */
function enable_auto_eager_loading(): void
{
    \App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig::enableGlobally();
}

/**
 * Disable auto-eager-loading globally.
 */
function disable_auto_eager_loading(): void
{
    \App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig::disableGlobally();
}

/**
 * Enable lazy loading detection (throws exception on N+1).
 * Only use in development environment.
 */
function detect_lazy_loading(): void
{
    \App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig::enableLazyLoadingDetection();
}

/**
 * Clear relation cache for a model or all models.
 *
 * @param class-string<Model>|null $modelClass
 */
function clear_relation_cache(?string $modelClass = null): void
{
    \App\Modules\Core\Infrastructure\Database\Eloquent\AutoEloquentRelations::clearRelationCache($modelClass);
}
