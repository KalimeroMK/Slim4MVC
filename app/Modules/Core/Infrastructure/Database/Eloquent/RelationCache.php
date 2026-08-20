<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Database\Eloquent;

final class RelationCache
{
    /** @var array<class-string, list<string>> */
    private static array $cache = [];

    /**
     * @param  class-string  $class
     * @return list<string>|null
     */
    public static function get(string $class): ?array
    {
        return self::$cache[$class] ?? null;
    }

    /**
     * @param  class-string  $class
     * @param  list<string>  $relations
     */
    public static function set(string $class, array $relations): void
    {
        self::$cache[$class] = $relations;
    }

    /**
     * @param  class-string  $class
     */
    public static function has(string $class): bool
    {
        return isset(self::$cache[$class]);
    }

    /**
     * @param  class-string|null  $class
     */
    public static function clear(?string $class = null): void
    {
        if ($class === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$class]);
        }
    }
}
