<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait for automatic eager loading of model relations.
 *
 * Usage:
 *   class User extends Model {
 *       use AutoEloquentRelations;
 *
 *       // Optional: Define default auto-loaded relations
 *       protected array $autoWith = ['roles', 'profile'];
 *
 *       // Optional: Exclude specific relations from auto-loading
 *       protected array $excludeAutoWith = ['passwordResets'];
 *   }
 *
 * Or call globally:
 *   AutoRelationLoader::enableGlobally();
 */
trait AutoEloquentRelations
{
    /** @var array<class-string<Model>, list<string>> Cached relation methods per model */
    private static array $relationCache = [];

    /**
     * Boot the auto eager loading functionality.
     */
    public static function bootAutoEloquentRelations(): void
    {
        static::addGlobalScope('auto_eager_load', function ($query) {
            $relations = static::getAutoLoadableRelations();

            if ($relations !== []) {
                $query->with($relations);
            }
        });
    }

    /**
     * Get relations that should be auto-loaded.
     *
     * @return list<string>
     */
    public static function getAutoLoadableRelations(): array
    {
        $class = static::class;

        // Check if model has explicit autoWith defined
        if (property_exists($class, 'autoWith')) {
            $instance = new static;

            return $instance->autoWith ?? [];
        }

        // Auto-detect all relations if enabled globally
        if (AutoRelationConfig::isAutoDetectionEnabled()) {
            return static::detectRelations();
        }

        return [];
    }

    /**
     * Detect all relation methods on this model using reflection.
     * Results are cached for performance.
     *
     * @return list<string>
     */
    public static function detectRelations(): array
    {
        $class = static::class;

        // Return cached result if available
        if (isset(self::$relationCache[$class])) {
            return self::$relationCache[$class];
        }

        $relations = [];
        $reflection = new ReflectionClass($class);
        $instance = new static;

        // Get excluded relations if defined
        $excluded = property_exists($class, 'excludeAutoWith')
            ? ($instance->excludeAutoWith ?? [])
            : [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            // Skip inherited methods from Model class
            if ($method->getDeclaringClass()->getName() === Model::class) {
                continue;
            }

            // Skip magic methods
            if (str_starts_with($methodName, '__')) {
                continue;
            }

            // Skip excluded relations
            if (in_array($methodName, $excluded, true)) {
                continue;
            }

            // Check if method returns a Relation
            if (! $method->hasReturnType()) {
                // Try to infer from method body (heuristic)
                if (static::isLikelyRelationMethod($method)) {
                    $relations[] = $methodName;
                }
                continue;
            }

            $returnType = $method->getReturnType();
            if ($returnType === null) {
                continue;
            }

            $typeName = $returnType->getName();

            // Check if return type is a Relation subclass
            if (is_a($typeName, Relation::class, true)) {
                $relations[] = $methodName;
            }
        }

        // Cache the result
        self::$relationCache[$class] = $relations;

        return $relations;
    }

    /**
     * Heuristic to check if a method is likely a relation method.
     * Used when return type is not explicitly declared.
     */
    private static function isLikelyRelationMethod(ReflectionMethod $method): bool
    {
        $filename = $method->getFileName();
        if ($filename === false) {
            return false;
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($startLine === false || $endLine === false) {
            return false;
        }

        $lines = file($filename);
        if ($lines === false) {
            return false;
        }

        $methodBody = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Check for common relation method calls
        $relationPatterns = [
            '->belongsTo(',
            '->hasOne(',
            '->hasMany(',
            '->belongsToMany(',
            '->morphTo(',
            '->morphOne(',
            '->morphMany(',
            '->morphToMany(',
        ];

        foreach ($relationPatterns as $pattern) {
            if (str_contains($methodBody, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear the relation cache for this model or all models.
     */
    public static function clearRelationCache(?string $modelClass = null): void
    {
        if ($modelClass === null) {
            self::$relationCache = [];
        } else {
            unset(self::$relationCache[$modelClass]);
        }
    }

    /**
     * Create a query without auto eager loading.
     *
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public static function queryWithoutAutoWith(): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()->withoutGlobalScope('auto_eager_load');
    }
}
