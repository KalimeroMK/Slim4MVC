<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Cache;

/**
 * Null cache implementation - used for testing or when caching is disabled.
 */
final class NullCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function deleteMultiple(array $keys): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return $callback();
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $callback();
    }

    public function increment(string $key, int $value = 1): int|false
    {
        return $value;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return -$value;
    }

    public function many(array $keys): array
    {
        return [];
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        return true;
    }

    public function getPrefixedKey(string $key): string
    {
        return $key;
    }

    public function flushByTag(string|array $tags): bool
    {
        return true;
    }
}
