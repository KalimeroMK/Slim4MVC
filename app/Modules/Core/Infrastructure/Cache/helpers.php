<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Cache\CacheManager;

if (! function_exists('cache')) {
    /**
     * Get the cache instance or perform cache operations.
     *
     * Usage:
     *   cache()                          // Get CacheInterface instance
     *   cache('key')                     // Get value by key
     *   cache('key', 'default')          // Get value with default
     *   cache(['key' => 'value'], 3600)  // Store value with TTL
     *
     * @param  string|array<string, mixed>|null  $key
     * @return CacheInterface|mixed
     */
    function cache(string|array|null $key = null, mixed $defaultOrTtl = null)
    {
        $cache = CacheManager::getInstance();

        // Return cache instance
        if ($key === null) {
            return $cache;
        }

        // Store multiple values
        if (is_array($key)) {
            $ttl = is_int($defaultOrTtl) ? $defaultOrTtl : 3600;

            return $cache->setMany($key, $ttl);
        }

        // Get value
        if ($defaultOrTtl === null || ! is_int($defaultOrTtl)) {
            return $cache->get($key, $defaultOrTtl);
        }

        // This shouldn't happen with normal usage
        return $cache->get($key);
    }
}

if (! function_exists('cache_remember')) {
    /**
     * Get an item from the cache, or execute the callback and store the result.
     *
     * @template T
     *
     * @param  int|null  $ttl  Time to live in seconds
     * @param  callable(): T  $callback
     * @return T
     */
    function cache_remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return CacheManager::getInstance()->remember($key, $ttl, $callback);
    }
}

if (! function_exists('cache_forever')) {
    /**
     * Get an item from the cache, or execute the callback and store it forever.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    function cache_forever(string $key, callable $callback): mixed
    {
        return CacheManager::getInstance()->rememberForever($key, $callback);
    }
}

if (! function_exists('cache_put')) {
    /**
     * Store an item in the cache.
     */
    function cache_put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return CacheManager::getInstance()->set($key, $value, $ttl);
    }
}

if (! function_exists('cache_forget')) {
    /**
     * Remove an item from the cache.
     */
    function cache_forget(string $key): bool
    {
        return CacheManager::getInstance()->delete($key);
    }
}

if (! function_exists('cache_flush')) {
    /**
     * Clear all items from the cache, or items by tag.
     *
     * @param  string|array<int, string>|null  $tags
     */
    function cache_flush(string|array|null $tags = null): bool
    {
        $cache = CacheManager::getInstance();

        if ($tags === null) {
            return $cache->clear();
        }

        return $cache->flushByTag($tags);
    }
}

if (! function_exists('cache_has')) {
    /**
     * Check if an item exists in the cache.
     */
    function cache_has(string $key): bool
    {
        return CacheManager::getInstance()->has($key);
    }
}

if (! function_exists('cache_increment')) {
    /**
     * Increment a numeric value in the cache.
     */
    function cache_increment(string $key, int $value = 1): int|false
    {
        return CacheManager::getInstance()->increment($key, $value);
    }
}

if (! function_exists('cache_decrement')) {
    /**
     * Decrement a numeric value in the cache.
     */
    function cache_decrement(string $key, int $value = 1): int|false
    {
        return CacheManager::getInstance()->decrement($key, $value);
    }
}

if (! function_exists('cache_many')) {
    /**
     * Get multiple items from the cache.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    function cache_many(array $keys): array
    {
        return CacheManager::getInstance()->many($keys);
    }
}

if (! function_exists('cache_set_many')) {
    /**
     * Store multiple items in the cache.
     *
     * @param  array<string, mixed>  $values
     */
    function cache_set_many(array $values, ?int $ttl = null): bool
    {
        return CacheManager::getInstance()->setMany($values, $ttl);
    }
}
