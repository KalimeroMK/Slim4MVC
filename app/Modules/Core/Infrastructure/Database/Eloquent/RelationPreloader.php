<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Service for preloading relations on demand.
 *
 * This is an alternative to auto-loading that gives you more control.
 * Instead of loading all relations automatically, you can preload specific
 * relations when needed.
 *
 * Usage:
 *   // Preload relations for a single model
 *   $user = RelationPreloader::load($user, ['roles', 'permissions']);
 *
 *   // Preload relations for a collection
 *   $users = RelationPreloader::loadMany($users, ['roles', 'profile']);
 *
 *   // Use the preload helper function
 *   $users = preload($users, ['roles', 'profile']);
 */
final class RelationPreloader
{
    /**
     * Preload relations on a single model.
     *
     * @template TModel of Model
     *
     * @param TModel $model
     * @param list<string>|string $relations
     * @return TModel
     */
    public static function load(Model $model, array|string $relations): Model
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        if ($relations === []) {
            return $model;
        }

        // Check which relations are already loaded
        $relationsToLoad = array_filter(
            $relations,
            fn ($relation): bool => ! $model->relationLoaded($relation)
        );

        if ($relationsToLoad === []) {
            return $model;
        }

        $model->load($relationsToLoad);

        return $model;
    }

    /**
     * Preload relations on a collection of models.
     *
     * @template TModel of Model
     *
     * @param Collection<int, TModel> $models
     * @param list<string>|string $relations
     * @return Collection<int, TModel>
     */
    public static function loadMany(Collection $models, array|string $relations): Collection
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        if ($relations === [] || $models->isEmpty()) {
            return $models;
        }

        // Check which relations need loading (if any model doesn't have it loaded)
        $relationsToLoad = [];

        foreach ($relations as $relation) {
            $needsLoading = $models->contains(
                fn (Model $model): bool => ! $model->relationLoaded($relation)
            );

            if ($needsLoading) {
                $relationsToLoad[] = $relation;
            }
        }

        if ($relationsToLoad === []) {
            return $models;
        }

        $models->load($relationsToLoad);

        return $models;
    }

    /**
     * Preload relations using a query builder.
     *
     * @template TModel of Model
     *
     * @param Builder<TModel> $builder
     * @param list<string>|string $relations
     * @return Builder<TModel>
     */
    public static function with(Builder $builder, array|string $relations): Builder
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        return $builder->with($relations);
    }

    /**
     * Load missing relations on a collection (only loads what's not already loaded).
     *
     * @template TModel of Model
     *
     * @param Collection<int, TModel> $models
     * @param list<string>|string $relations
     * @return Collection<int, TModel>
     */
    public static function loadMissing(Collection $models, array|string $relations): Collection
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        if ($relations === [] || $models->isEmpty()) {
            return $models;
        }

        $models->loadMissing($relations);

        return $models;
    }

    /**
     * Check if all models in a collection have relations loaded.
     *
     * @template TModel of Model
     *
     * @param Collection<int, TModel> $models
     * @param list<string>|string $relations
     */
    public static function hasLoaded(Collection $models, array|string $relations): bool
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        if ($relations === [] || $models->isEmpty()) {
            return true;
        }

        foreach ($relations as $relation) {
            $allLoaded = $models->every(
                fn (Model $model) => $model->relationLoaded($relation)
            );

            if (! $allLoaded) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of relations that are not loaded.
     *
     * @template TModel of Model
     *
     * @param Collection<int, TModel> $models
     * @param list<string>|string $relations
     * @return list<string>
     */
    public static function getMissingRelations(Collection $models, array|string $relations): array
    {
        $relations = is_string($relations) ? [$relations] : $relations;

        if ($relations === [] || $models->isEmpty()) {
            return [];
        }

        $missing = [];

        foreach ($relations as $relation) {
            $someMissing = $models->contains(
                fn (Model $model): bool => ! $model->relationLoaded($relation)
            );

            if ($someMissing) {
                $missing[] = $relation;
            }
        }

        return $missing;
    }
}
