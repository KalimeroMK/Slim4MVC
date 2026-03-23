<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Cache;

/**
 * Trait for cacheable entities.
 * Use in repositories or services for easy caching.
 */
trait Cacheable
{
    protected ?CacheInterface $cache = null;

    protected int $cacheTtl = 3600; // 1 hour default

    protected string $cacheTag = '';

    /**
     * Set the cache instance.
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Get the cache instance.
     */
    public function getCache(): CacheInterface
    {
        if ($this->cache === null) {
            $this->cache = CacheManager::getInstance();
        }

        return $this->cache;
    }

    /**
     * Set cache TTL in seconds.
     */
    public function setCacheTtl(int $seconds): void
    {
        $this->cacheTtl = $seconds;
    }

    /**
     * Get cache TTL.
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Set cache tag for this entity.
     */
    public function setCacheTag(string $tag): void
    {
        $this->cacheTag = $tag;
    }

    /**
     * Get cache tag.
     */
    public function getCacheTag(): string
    {
        return $this->cacheTag ?: static::class;
    }

    /**
     * Generate a cache key.
     */
    protected function cacheKey(string $suffix = ''): string
    {
        $base = $this->getCacheTag();

        return $suffix !== '' ? sprintf('%s:%s', $base, $suffix) : $base;
    }

    /**
     * Get item from cache or execute callback.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function remember(string $key, callable $callback): mixed
    {
        return $this->getCache()->remember(
            $this->cacheKey($key),
            $this->cacheTtl,
            $callback
        );
    }

    /**
     * Get item from cache with specific TTL.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function rememberFor(string $key, ?int $ttl, callable $callback): mixed
    {
        return $this->getCache()->remember(
            $this->cacheKey($key),
            $ttl,
            $callback
        );
    }

    /**
     * Store item in cache.
     */
    protected function cacheSet(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->getCache()->set(
            $this->cacheKey($key),
            $value,
            $ttl ?? $this->cacheTtl
        );
    }

    /**
     * Get item from cache.
     */
    protected function cacheGet(string $key, mixed $default = null): mixed
    {
        return $this->getCache()->get($this->cacheKey($key), $default);
    }

    /**
     * Check if item exists in cache.
     */
    protected function cacheHas(string $key): bool
    {
        return $this->getCache()->has($this->cacheKey($key));
    }

    /**
     * Delete item from cache.
     */
    protected function cacheDelete(string $key): bool
    {
        return $this->getCache()->delete($this->cacheKey($key));
    }

    /**
     * Flush all cache for this entity.
     */
    protected function cacheFlush(): bool
    {
        return $this->getCache()->flushByTag($this->getCacheTag());
    }

    /**
     * Clear specific cache keys for this entity.
     *
     * @param  array<int, string>  $keys
     */
    protected function cacheDeleteMultiple(array $keys): bool
    {
        $prefixedKeys = array_map(
            fn (string $key): string => $this->cacheKey($key),
            $keys
        );

        return $this->getCache()->deleteMultiple($prefixedKeys);
    }
}
