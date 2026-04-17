<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Cache\CacheManager;
use App\Modules\Core\Infrastructure\Cache\FileCache;
use App\Modules\Core\Infrastructure\Cache\NullCache;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    private string $testCachePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testCachePath = __DIR__.'/../../../storage/cache/test';

        // Clean up test cache directory
        if (is_dir($this->testCachePath)) {
            $files = glob($this->testCachePath.'/*.cache');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }

        // Reset CacheManager singleton
        CacheManager::resetInstance();
    }

    protected function tearDown(): void
    {
        // Clean up test cache directory
        if (is_dir($this->testCachePath)) {
            $files = glob($this->testCachePath.'/*.cache');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }

        parent::tearDown();
    }

    public function test_null_cache_always_returns_default(): void
    {
        $nullCache = new NullCache();

        $this->assertNull($nullCache->get('key'));
        $this->assertEquals('default', $nullCache->get('key', 'default'));
        $this->assertFalse($nullCache->has('key'));
        $this->assertTrue($nullCache->set('key', 'value'));
        $this->assertFalse($nullCache->has('key'));
    }

    public function test_null_cache_remember_executes_callback(): void
    {
        $nullCache = new NullCache();
        $called = false;

        $result = $nullCache->remember('key', 60, function () use (&$called): string {
            $called = true;

            return 'computed';
        });

        $this->assertTrue($called);
        $this->assertEquals('computed', $result);
    }

    public function test_file_cache_stores_and_retrieves_values(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        $this->assertTrue($fileCache->set('key', 'value'));
        $this->assertEquals('value', $fileCache->get('key'));
        $this->assertTrue($fileCache->has('key'));
    }

    public function test_file_cache_returns_default_for_missing_key(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        $this->assertNull($fileCache->get('nonexistent'));
        $this->assertEquals('default', $fileCache->get('nonexistent', 'default'));
    }

    public function test_file_cache_deletes_value(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        $fileCache->set('key', 'value');
        $this->assertTrue($fileCache->has('key'));

        $this->assertTrue($fileCache->delete('key'));
        $this->assertFalse($fileCache->has('key'));
    }

    public function test_file_cache_respects_ttl(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        // Set with very short TTL
        $fileCache->set('key', 'value', 1);
        $this->assertEquals('value', $fileCache->get('key'));

        // Wait for expiration
        sleep(2);

        $this->assertNull($fileCache->get('key'));
        $this->assertFalse($fileCache->has('key'));
    }

    public function test_file_cache_remember_stores_callback_result(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');
        $callCount = 0;

        $result1 = $fileCache->remember('key', 3600, function () use (&$callCount): string {
            $callCount++;

            return 'computed';
        });

        $this->assertSame(1, $callCount);
        $this->assertEquals('computed', $result1);

        // Second call should use cached value
        $result2 = $fileCache->remember('key', 3600, function () use (&$callCount): string {
            $callCount++;

            return 'new_value';
        });

        $this->assertSame(1, $callCount); // Callback not called again
        $this->assertEquals('computed', $result2);
    }

    public function test_file_cache_clear_removes_all_values(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        $fileCache->set('key1', 'value1');
        $fileCache->set('key2', 'value2');

        $this->assertTrue($fileCache->clear());

        $this->assertNull($fileCache->get('key1'));
        $this->assertNull($fileCache->get('key2'));
    }

    public function test_file_cache_increment_and_decrement(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        $fileCache->set('counter', 10);

        $this->assertSame(11, $fileCache->increment('counter'));
        $this->assertSame(15, $fileCache->increment('counter', 4));

        $this->assertSame(14, $fileCache->decrement('counter'));
        $this->assertSame(10, $fileCache->decrement('counter', 4));
    }

    public function test_file_cache_many_operations(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        $fileCache->setMany(['key1' => 'value1', 'key2' => 'value2']);

        $results = $fileCache->many(['key1', 'key2', 'key3']);

        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2', 'key3' => null], $results);
    }

    public function test_file_cache_delete_multiple(): void
    {
        $fileCache = new FileCache($this->testCachePath, 'test');

        $fileCache->set('key1', 'value1');
        $fileCache->set('key2', 'value2');
        $fileCache->set('key3', 'value3');

        $fileCache->deleteMultiple(['key1', 'key2']);

        $this->assertNull($fileCache->get('key1'));
        $this->assertNull($fileCache->get('key2'));
        $this->assertEquals('value3', $fileCache->get('key3'));
    }

    public function test_cache_manager_creates_different_drivers(): void
    {
        $cacheManager = new CacheManager();

        $nullCache = $cacheManager->driver('null');
        $this->assertInstanceOf(NullCache::class, $nullCache);

        $fileCache = $cacheManager->driver('file');
        $this->assertInstanceOf(FileCache::class, $fileCache);
    }

    public function test_cache_manager_get_instance_returns_singleton(): void
    {
        CacheManager::resetInstance();

        $cache = CacheManager::getInstance();
        $instance2 = CacheManager::getInstance();

        $this->assertSame($cache, $instance2);
    }

    public function test_cache_helper_functions(): void
    {
        CacheManager::resetInstance();
        $_ENV['CACHE_DRIVER'] = 'null';

        // Test cache() with no args returns CacheInterface
        $this->assertInstanceOf(CacheInterface::class, cache());

        // Test cache_put and cache_get
        $this->assertTrue(cache_put('test_key', 'test_value'));

        // Null cache always returns default
        $this->assertNull(cache('test_key'));

        // Test cache_has
        $this->assertFalse(cache_has('test_key'));

        // Test cache_forget
        $this->assertTrue(cache_forget('test_key'));

        // Test cache_flush
        $this->assertTrue(cache_flush());
    }

    public function test_cache_remember_helper(): void
    {
        CacheManager::resetInstance();
        $_ENV['CACHE_DRIVER'] = 'null';

        $callCount = 0;

        $result = cache_remember('key', 60, function () use (&$callCount): string {
            $callCount++;

            return 'computed_value';
        });

        $this->assertSame(1, $callCount);
        $this->assertEquals('computed_value', $result);
    }
}
