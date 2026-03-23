<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Cache;

/**
 * Cache interface for different storage implementations.
 */
interface CacheInterface
{
    /**
     * Get an item from the cache.
     *
     * @template T
     *
     * @param  string  $key  The cache key
     * @param  T|null  $default  Default value if key not found
     * @return T|null
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache.
     *
     * @param  string  $key  The cache key
     * @param  mixed  $value  The value to store
     * @param  int|null  $ttl  Time to live in seconds (null = forever)
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Check if an item exists in the cache.
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the cache.
     */
    public function delete(string $key): bool;

    /**
     * Remove multiple items from the cache.
     *
     * @param  array<int, string>  $keys
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Clear all items from the cache.
     */
    public function clear(): bool;

    /**
     * Get an item from the cache, or execute the callback and store the result.
     *
     * @template T
     *
     * @param  string  $key  The cache key
     * @param  int|null  $ttl  Time to live in seconds
     * @param  callable(): T  $callback  Callback to execute if key not found
     * @return T
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed;

    /**
     * Get an item from the cache, or execute the callback and store the result forever.
     *
     * @template T
     *
     * @param  string  $key  The cache key
     * @param  callable(): T  $callback  Callback to execute if key not found
     * @return T
     */
    public function rememberForever(string $key, callable $callback): mixed;

    /**
     * Increment a numeric value in the cache.
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Decrement a numeric value in the cache.
     */
    public function decrement(string $key, int $value = 1): int|false;

    /**
     * Get multiple items from the cache.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function many(array $keys): array;

    /**
     * Store multiple items in the cache.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, ?int $ttl = null): bool;

    /**
     * Get the cache key with prefix applied.
     */
    public function getPrefixedKey(string $key): string;

    /**
     * Flush cache items with a specific tag or prefix.
     *
     * @param  string|array<int, string>  $tags
     */
    public function flushByTag(string|array $tags): bool;
}
