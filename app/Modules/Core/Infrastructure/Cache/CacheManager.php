<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Cache;

use InvalidArgumentException;
use Predis\Client;

/**
 * Cache Manager - factory for cache instances.
 */
final class CacheManager
{
    private static ?CacheInterface $cache = null;

    private readonly string $defaultDriver;

    private readonly ?string $prefix;

    public function __construct(
        ?string $driver = null,
        ?string $prefix = null
    ) {
        $this->defaultDriver = $driver ?? $_ENV['CACHE_DRIVER'] ?? 'file';
        $this->prefix = $prefix ?? $_ENV['CACHE_PREFIX'] ?? 'slim_cache';
    }

    /**
     * Get the default cache instance (singleton).
     */
    public static function getInstance(?string $driver = null, ?string $prefix = null): CacheInterface
    {
        if (self::$cache === null) {
            $manager = new self($driver, $prefix);
            self::$cache = $manager->driver();
        }

        return self::$cache;
    }

    /**
     * Reset the singleton instance (useful for testing).
     */
    public static function resetInstance(): void
    {
        self::$cache = null;
    }

    /**
     * Get cache instance by driver name.
     */
    public function driver(?string $name = null): CacheInterface
    {
        $name ??= $this->defaultDriver;

        return match ($name) {
            'redis' => $this->createRedisDriver(),
            'file' => $this->createFileDriver(),
            'null', 'none', 'array' => $this->createNullDriver(),
            default => throw new InvalidArgumentException('Unsupported cache driver: '.$name),
        };
    }

    /**
     * Get an item from the default cache.
     *
     * @template T
     *
     * @param  T|null  $default
     * @return T|null
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver()->get($key, $default);
    }

    /**
     * Store an item in the default cache.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->driver()->set($key, $value, $ttl);
    }

    /**
     * Get an item from the cache, or execute the callback and store the result.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return $this->driver()->remember($key, $ttl, $callback);
    }

    /**
     * Remove an item from the default cache.
     */
    public function delete(string $key): bool
    {
        return $this->driver()->delete($key);
    }

    /**
     * Clear all items from the default cache.
     */
    public function clear(): bool
    {
        return $this->driver()->clear();
    }

    /**
     * Check if an item exists in the default cache.
     */
    public function has(string $key): bool
    {
        return $this->driver()->has($key);
    }

    /**
     * Flush cache items by tag(s).
     *
     * @param  string|array<int, string>  $tags
     */
    public function flushByTag(string|array $tags): bool
    {
        return $this->driver()->flushByTag($tags);
    }

    /**
     * Create Redis cache driver.
     */
    private function createRedisDriver(): CacheInterface
    {
        $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $port = (int) ($_ENV['REDIS_PORT'] ?? 6379);
        $password = $_ENV['REDIS_PASSWORD'] ?? null;
        $database = (int) ($_ENV['REDIS_CACHE_DATABASE'] ?? 1);

        $parameters = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
        ];

        if (! in_array($password, [null, '', 'null'], true)) {
            $parameters['password'] = $password;
        }

        $client = new Client($parameters);

        return new RedisCache($client, $this->prefix);
    }

    /**
     * Create file cache driver.
     */
    private function createFileDriver(): CacheInterface
    {
        $cachePath = $_ENV['CACHE_PATH'] ?? __DIR__.'/../../../../../../storage/cache/data';

        return new FileCache($cachePath, $this->prefix);
    }

    /**
     * Create null cache driver (for testing).
     */
    private function createNullDriver(): CacheInterface
    {
        return new NullCache();
    }
}
