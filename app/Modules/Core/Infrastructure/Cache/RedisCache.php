<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Cache;

use Predis\Client;
use Predis\Response\Status;
use Throwable;

/**
 * Redis-based cache implementation.
 */
final readonly class RedisCache implements CacheInterface
{
    private Client $client;

    private string $prefix;

    public function __construct(
        ?Client $client = null,
        ?string $prefix = null
    ) {
        $this->client = $client ?? $this->createClient();
        $this->prefix = $prefix ?? ($_ENV['CACHE_PREFIX'] ?? 'slim_cache');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $prefixedKey = $this->getPrefixedKey($key);
        $value = $this->client->get($prefixedKey);

        if ($value === null) {
            return $default;
        }

        return $this->unserialize($value);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $prefixedKey = $this->getPrefixedKey($key);
        $serialized = $this->serialize($value);

        if ($ttl === null) {
            $result = $this->client->set($prefixedKey, $serialized);
        } else {
            $result = $this->client->setex($prefixedKey, $ttl, $serialized);
        }

        return $result instanceof Status && (string) $result === 'OK';
    }

    public function has(string $key): bool
    {
        $prefixedKey = $this->getPrefixedKey($key);

        return (bool) $this->client->exists($prefixedKey);
    }

    public function delete(string $key): bool
    {
        $prefixedKey = $this->getPrefixedKey($key);

        return (bool) $this->client->del([$prefixedKey]);
    }

    public function deleteMultiple(array $keys): bool
    {
        if ($keys === []) {
            return true;
        }

        $prefixedKeys = array_map(
            $this->getPrefixedKey(...),
            $keys
        );

        return (bool) $this->client->del($prefixedKeys);
    }

    public function clear(): bool
    {
        // Use SCAN to find keys with our prefix and delete them
        $iterator = null;
        $pattern = $this->prefix.'_*';

        do {
            $result = $this->client->scan($iterator, ['MATCH' => $pattern, 'COUNT' => 100]);

            /** @phpstan-ignore-next-line */
            if ($result === false || ! is_array($result)) {
                break;
            }

            $iterator = $result[0];
            $keys = $result[1] ?? [];

            if ($keys !== []) {
                $this->client->del($keys);
            }
        } while ($iterator !== 0);

        return true;
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        /** @phpstan-ignore-next-line */
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, null, $callback);
    }

    public function increment(string $key, int $value = 1): int|false
    {
        $prefixedKey = $this->getPrefixedKey($key);

        // Check if key exists and is numeric
        $current = $this->client->get($prefixedKey);

        if ($current !== null) {
            $unserialized = $this->unserialize($current);

            /** @phpstan-ignore-next-line */
            if (! is_int($unserialized)) {
                return false;
            }
        }

        $newValue = $this->client->incrby($prefixedKey, $value);

        /** @phpstan-ignore-next-line */
        return is_int($newValue) ? $newValue : false;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    public function many(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $prefixedKeys = array_map(
            $this->getPrefixedKey(...),
            $keys
        );

        $values = $this->client->mget($prefixedKeys);
        $result = [];

        foreach ($keys as $index => $key) {
            $value = $values[$index] ?? null;
            $result[$key] = $value !== null ? $this->unserialize($value) : null;
        }

        return $result;
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        if ($values === []) {
            return true;
        }

        $pipeline = $this->client->pipeline();

        /** @phpstan-ignore-next-line */
        if (! is_array($pipeline)) {
            foreach ($values as $key => $value) {
                $prefixedKey = $this->getPrefixedKey($key);
                $serialized = $this->serialize($value);

                if ($ttl === null) {
                    $pipeline->set($prefixedKey, $serialized);
                } else {
                    $pipeline->setex($prefixedKey, $ttl, $serialized);
                }
            }

            $results = $pipeline->execute();
        } else {
            $results = [];
        }

        // Check if all operations succeeded
        foreach ($results as $result) {
            if ($result instanceof Status && (string) $result !== 'OK') {
                return false;
            }
        }

        return true;
    }

    public function getPrefixedKey(string $key): string
    {
        return $this->prefix.':'.$key;
    }

    public function flushByTag(string|array $tags): bool
    {
        $tags = is_array($tags) ? $tags : [$tags];

        // Use SCAN to find keys matching any tag
        $iterator = null;
        $pattern = $this->prefix.':*';
        $keysToDelete = [];

        do {
            $result = $this->client->scan($iterator, ['MATCH' => $pattern, 'COUNT' => 100]);

            /** @phpstan-ignore-next-line */
            if ($result === false || ! is_array($result)) {
                break;
            }

            $iterator = $result[0];
            $keys = $result[1] ?? [];

            foreach ($keys as $key) {
                $keyStr = (string) $key;

                foreach ($tags as $tag) {
                    if (str_contains($keyStr, $tag)) {
                        $keysToDelete[] = $keyStr;
                        break;
                    }
                }
            }
        } while ($iterator !== 0);

        if ($keysToDelete !== []) {
            // Delete in chunks to avoid blocking Redis
            $chunks = array_chunk($keysToDelete, 100);

            foreach ($chunks as $chunk) {
                $this->client->del($chunk);
            }
        }

        return true;
    }

    /**
     * Create Redis client from environment configuration.
     */
    private function createClient(): Client
    {
        $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $port = (int) ($_ENV['REDIS_PORT'] ?? 6379);
        $password = $_ENV['REDIS_PASSWORD'] ?? null;
        $database = (int) ($_ENV['REDIS_CACHE_DATABASE'] ?? 1); // Use DB 1 for cache

        $parameters = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
        ];

        if (! in_array($password, [null, '', 'null'], true)) {
            $parameters['password'] = $password;
        }

        return new Client($parameters);
    }

    /**
     * Serialize value for storage.
     */
    private function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * Unserialize value from storage.
     */
    private function unserialize(string $value): mixed
    {
        try {
            return @unserialize($value);
        } catch (Throwable) {
            return null;
        }
    }
}
