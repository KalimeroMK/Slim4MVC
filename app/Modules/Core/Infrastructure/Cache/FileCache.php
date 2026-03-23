<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Cache;

use RuntimeException;
use Throwable;

/**
 * File-based cache implementation.
 */
final readonly class FileCache implements CacheInterface
{
    private string $cachePath;

    private string $prefix;

    public function __construct(
        ?string $cachePath = null,
        ?string $prefix = null
    ) {
        $this->cachePath = $cachePath ?? __DIR__.'/../../../../../../storage/cache/data';
        $this->prefix = $prefix ?? ($_ENV['CACHE_PREFIX'] ?? 'slim_cache');

        $this->ensureDirectoryExists();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getCacheFile($key);

        if (! file_exists($file)) {
            return $default;
        }

        $data = $this->readFile($file);

        if ($data === null) {
            return $default;
        }

        // Check expiration
        if ($data['expires'] !== null && $data['expires'] < time()) {
            $this->delete($key);

            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $file = $this->cachePath.'/'.$this->getPrefixedKey($key).'.cache';
        $expires = $ttl === null ? null : time() + $ttl;

        $data = [
            'expires' => $expires,
            'value' => $value,
        ];

        $content = serialize($data);
        $tempFile = $file.'.tmp';

        // Atomic write using temp file
        if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
            return false;
        }

        if (! rename($tempFile, $file)) {
            unlink($tempFile);

            return false;
        }

        return true;
    }

    public function has(string $key): bool
    {
        $file = $this->cachePath.'/'.$this->getPrefixedKey($key).'.cache';

        if (! file_exists($file)) {
            return false;
        }

        $data = $this->readFile($file);

        if ($data === null) {
            return false;
        }

        if ($data['expires'] !== null && $data['expires'] < time()) {
            $this->delete($key);

            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        $file = $this->cachePath.'/'.$this->getPrefixedKey($key).'.cache';

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function deleteMultiple(array $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (! $this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    public function clear(): bool
    {
        $files = glob($this->cachePath.'/*.cache');

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

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
        $current = $this->get($key, 0);

        if (! is_int($current)) {
            return false;
        }

        $newValue = $current + $value;
        $this->set($key, $newValue);

        return $newValue;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    public function many(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function getPrefixedKey(string $key): string
    {
        return $this->prefix.'_'.$this->sanitizeKey($key);
    }

    public function flushByTag(string|array $tags): bool
    {
        $tags = is_array($tags) ? $tags : [$tags];
        $files = glob($this->cachePath.'/*.cache');

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            $filename = basename($file, '.cache');

            foreach ($tags as $tag) {
                if (str_contains($filename, $tag)) {
                    unlink($file);
                    break;
                }
            }
        }

        return true;
    }

    /**
     * Get the full path to a cache file.
     */
    private function getCacheFile(string $key): string
    {
        return $this->cachePath.'/'.$this->getPrefixedKey($key).'.cache';
    }

    /**
     * Read and unserialize cache file.
     *
     * @return array{expires: int|null, value: mixed}|null
     */
    private function readFile(string $file): ?array
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return null;
        }

        try {
            $data = @unserialize($content);

            if (! is_array($data) || ! array_key_exists('expires', $data) || ! array_key_exists('value', $data)) {
                return null;
            }

            return $data;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Ensure the cache directory exists.
     */
    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->cachePath) && (! mkdir($this->cachePath, 0755, true) && ! is_dir($this->cachePath))) {
            throw new RuntimeException('Failed to create cache directory: '.$this->cachePath);
        }
    }

    /**
     * Sanitize cache key for filesystem safety.
     */
    private function sanitizeKey(string $key): string
    {
        // Replace problematic characters
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);

        if ($sanitized === null || $sanitized === '') {
            return md5($key);
        }

        // Limit length
        if (mb_strlen($sanitized) > 200) {
            return mb_substr($sanitized, 0, 100).'_'.md5($key);
        }

        return $sanitized;
    }
}
