<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Modules\Core\Infrastructure\Cache\CacheManager;
use App\Modules\Core\Infrastructure\Cache\FileCache;
use App\Modules\Core\Infrastructure\Cache\NullCache;
use App\Modules\Core\Infrastructure\Cache\CacheInterface;
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
        $cache = new NullCache();
        
        $this->assertNull($cache->get('key'));
        $this->assertEquals('default', $cache->get('key', 'default'));
        $this->assertFalse($cache->has('key'));
        $this->assertTrue($cache->set('key', 'value'));
        $this->assertFalse($cache->has('key'));
    }

    public function test_null_cache_remember_executes_callback(): void
    {
        $cache = new NullCache();
        $called = false;
        
        $result = $cache->remember('key', 60, function () use (&$called) {
            $called = true;
            return 'computed';
        });
        
        $this->assertTrue($called);
        $this->assertEquals('computed', $result);
    }

    public function test_file_cache_stores_and_retrieves_values(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        $this->assertTrue($cache->set('key', 'value'));
        $this->assertEquals('value', $cache->get('key'));
        $this->assertTrue($cache->has('key'));
    }

    public function test_file_cache_returns_default_for_missing_key(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        $this->assertNull($cache->get('nonexistent'));
        $this->assertEquals('default', $cache->get('nonexistent', 'default'));
    }

    public function test_file_cache_deletes_value(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        $cache->set('key', 'value');
        $this->assertTrue($cache->has('key'));
        
        $this->assertTrue($cache->delete('key'));
        $this->assertFalse($cache->has('key'));
    }

    public function test_file_cache_respects_ttl(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        // Set with very short TTL
        $cache->set('key', 'value', 1);
        $this->assertEquals('value', $cache->get('key'));
        
        // Wait for expiration
        sleep(2);
        
        $this->assertNull($cache->get('key'));
        $this->assertFalse($cache->has('key'));
    }

    public function test_file_cache_remember_stores_callback_result(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        $callCount = 0;
        
        $result1 = $cache->remember('key', 3600, function () use (&$callCount) {
            $callCount++;
            return 'computed';
        });
        
        $this->assertEquals(1, $callCount);
        $this->assertEquals('computed', $result1);
        
        // Second call should use cached value
        $result2 = $cache->remember('key', 3600, function () use (&$callCount) {
            $callCount++;
            return 'new_value';
        });
        
        $this->assertEquals(1, $callCount); // Callback not called again
        $this->assertEquals('computed', $result2);
    }

    public function test_file_cache_clear_removes_all_values(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        
        $this->assertTrue($cache->clear());
        
        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
    }

    public function test_file_cache_increment_and_decrement(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        $cache->set('counter', 10);
        
        $this->assertEquals(11, $cache->increment('counter'));
        $this->assertEquals(15, $cache->increment('counter', 4));
        
        $this->assertEquals(14, $cache->decrement('counter'));
        $this->assertEquals(10, $cache->decrement('counter', 4));
    }

    public function test_file_cache_many_operations(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        $cache->setMany(['key1' => 'value1', 'key2' => 'value2']);
        
        $results = $cache->many(['key1', 'key2', 'key3']);
        
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2', 'key3' => null], $results);
    }

    public function test_file_cache_delete_multiple(): void
    {
        $cache = new FileCache($this->testCachePath, 'test');
        
        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');
        
        $cache->deleteMultiple(['key1', 'key2']);
        
        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
        $this->assertEquals('value3', $cache->get('key3'));
    }

    public function test_cache_manager_creates_different_drivers(): void
    {
        $manager = new CacheManager();
        
        $nullCache = $manager->driver('null');
        $this->assertInstanceOf(NullCache::class, $nullCache);
        
        $fileCache = $manager->driver('file');
        $this->assertInstanceOf(FileCache::class, $fileCache);
    }

    public function test_cache_manager_get_instance_returns_singleton(): void
    {
        CacheManager::resetInstance();
        
        $instance1 = CacheManager::getInstance();
        $instance2 = CacheManager::getInstance();
        
        $this->assertSame($instance1, $instance2);
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
        
        $result = cache_remember('key', 60, function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        });
        
        $this->assertEquals(1, $callCount);
        $this->assertEquals('computed_value', $result);
    }
}
