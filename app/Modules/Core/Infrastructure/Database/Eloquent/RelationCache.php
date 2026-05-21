<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Database\Eloquent;

final class RelationCache
{
    /** @var array<class-string, list<string>> */
    private static array $cache = [];

    /** @return list<string>|null */
    public static function get(string $class): ?array
    {
        return self::$cache[$class] ?? null;
    }

    /** @param list<string> $relations */
    public static function set(string $class, array $relations): void
    {
        self::$cache[$class] = $relations;
    }

    public static function has(string $class): bool
    {
        return isset(self::$cache[$class]);
    }

    public static function clear(?string $class = null): void
    {
        if ($class === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$class]);
        }
    }
}
