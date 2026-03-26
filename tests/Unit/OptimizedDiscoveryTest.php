<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\DI\OptimizedDiscovery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\Core\Infrastructure\DI\OptimizedDiscovery
 */
class OptimizedDiscoveryTest extends TestCase
{
    private string $cacheFile;
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheFile = OptimizedDiscovery::getCacheFile();
        $this->originalEnv = $_ENV;

        // Clear cache before each test
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;

        // Clean up cache file
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        parent::tearDown();
    }

    public function test_buildDefinitions_returns_array(): void
    {
        $discovery = new OptimizedDiscovery();
        $definitions = $discovery->buildDefinitions();

        $this->assertIsArray($definitions);
    }

    public function test_buildDefinitions_creates_cache_file(): void
    {
        $this->assertFileDoesNotExist($this->cacheFile);

        $discovery = new OptimizedDiscovery();
        $discovery->buildDefinitions();

        $this->assertFileExists($this->cacheFile);
    }

    public function test_shouldUseCache_returns_false_when_no_cache(): void
    {
        $this->assertFileDoesNotExist($this->cacheFile);

        $discovery = new OptimizedDiscovery();
        $this->assertFalse($discovery->shouldUseCache());
    }

    public function test_shouldUseCache_returns_true_when_cache_exists_in_production(): void
    {
        $_ENV['APP_ENV'] = 'production';

        // Create cache file
        file_put_contents($this->cacheFile, '<?php return [];');

        $discovery = new OptimizedDiscovery();
        $this->assertTrue($discovery->shouldUseCache());
    }

    public function test_shouldUseCache_returns_false_for_expired_cache_in_development(): void
    {
        $_ENV['APP_ENV'] = 'local';

        // Create old cache file (more than 1 hour ago)
        file_put_contents($this->cacheFile, '<?php return [];');
        touch($this->cacheFile, time() - 7200); // 2 hours ago

        $discovery = new OptimizedDiscovery();
        $this->assertFalse($discovery->shouldUseCache());
    }

    public function test_warmCache_returns_stats(): void
    {
        $discovery = new OptimizedDiscovery();
        $result = $discovery->warmCache();

        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertIsInt($result['count']);
        $this->assertIsFloat($result['duration']);
        $this->assertGreaterThanOrEqual(0, $result['duration']);
    }

    public function test_clearCache_removes_cache_file(): void
    {
        // Create cache file
        file_put_contents($this->cacheFile, '<?php return [];');
        $this->assertFileExists($this->cacheFile);

        $discovery = new OptimizedDiscovery();
        $result = $discovery->clearCache();

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function test_clearCache_returns_true_when_no_cache(): void
    {
        $this->assertFileDoesNotExist($this->cacheFile);

        $discovery = new OptimizedDiscovery();
        $result = $discovery->clearCache();

        $this->assertTrue($result);
    }

    public function test_getStats_returns_expected_structure(): void
    {
        $discovery = new OptimizedDiscovery();
        $stats = $discovery->getStats();

        $this->assertArrayHasKey('total_bindings', $stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
        $this->assertArrayHasKey('cache_file', $stats);
        $this->assertArrayHasKey('cache_exists', $stats);
        $this->assertArrayHasKey('cache_valid', $stats);
        $this->assertArrayHasKey('environment', $stats);
        $this->assertArrayHasKey('sample_bindings', $stats);

        $this->assertIsInt($stats['total_bindings']);
        $this->assertIsBool($stats['cache_enabled']);
        $this->assertIsString($stats['cache_file']);
        $this->assertIsBool($stats['cache_exists']);
        $this->assertIsBool($stats['cache_valid']);
        $this->assertIsArray($stats['sample_bindings']);
    }

    public function test_cached_definitions_is_array(): void
    {
        $discovery = new OptimizedDiscovery();

        // Build definitions (creates cache)
        $discovery->buildDefinitions();

        // Get cached definitions
        $cachedDefinitions = require $this->cacheFile;

        $this->assertIsArray($cachedDefinitions);
    }

    public function test_buildDefinitions_uses_cache_when_valid(): void
    {
        $_ENV['APP_ENV'] = 'production';

        // Create initial cache
        $discovery = new OptimizedDiscovery();
        $discovery->warmCache();

        // Get a new discovery instance
        $newDiscovery = new OptimizedDiscovery();

        // Should use cache
        $this->assertTrue($newDiscovery->shouldUseCache());
    }

    public function test_getCacheFile_returns_correct_path(): void
    {
        $path = OptimizedDiscovery::getCacheFile();

        $this->assertIsString($path);
        $this->assertStringContainsString('autowiring.php', $path);
    }
}
