<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\DiscoveryCommand;
use App\Modules\Core\Infrastructure\DI\OptimizedDiscovery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Feature tests for Discovery CLI Command.
 * 
 * Tests the actual CLI command execution.
 * 
 * @group feature
 */
class DiscoveryCommandFeatureTest extends TestCase
{
    private CommandTester $commandTester;
    private string $cacheFile;
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheFile = OptimizedDiscovery::getCacheFile();
        $this->originalEnv = $_ENV;

        // Clear cache
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        $application = new Application();
        $application->add(new DiscoveryCommand());

        $command = $application->find('discovery');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;

        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        parent::tearDown();
    }

    /**
     */
    public function test_it_shows_stats_by_default(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(0, $statusCode);
        $this->assertStringContainsString('Auto-Discovery Statistics', $output);
        $this->assertStringContainsString('Total Bindings', $output);
    }

    /**
     */
    public function test_it_validates_environment(): void
    {
        $_ENV = [
            'JWT_SECRET' => 'valid-secret-key-32-chars-long!!',
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'local',
        ];

        $this->commandTester->execute(['--validate' => true]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(0, $statusCode);
        $this->assertStringContainsString('Environment Validation', $output);
        $this->assertStringContainsString('Environment configuration is valid', $output);
    }

    /**
     */
    public function test_it_shows_validation_errors_for_invalid_config(): void
    {
        $_ENV = ['APP_ENV' => 'local']; // Missing required vars

        $this->commandTester->execute(['--validate' => true]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(1, $statusCode);
        $this->assertStringContainsString('CONFIGURATION VALIDATION FAILED', $output);
    }

    /**
     */
    public function test_it_warms_cache(): void
    {
        $this->assertFileDoesNotExist($this->cacheFile);

        $this->commandTester->execute(['--warm' => true]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(0, $statusCode);
        $this->assertStringContainsString('Warming Discovery Cache', $output);
        $this->assertStringContainsString('Cache warmed successfully', $output);
        $this->assertFileExists($this->cacheFile);
    }

    /**
     */
    public function test_it_clears_cache(): void
    {
        // First warm the cache
        file_put_contents($this->cacheFile, '<?php return [];');
        $this->assertFileExists($this->cacheFile);

        $this->commandTester->execute(['--clear' => true]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(0, $statusCode);
        $this->assertStringContainsString('Clearing Discovery Cache', $output);
        $this->assertFileDoesNotExist($this->cacheFile);
    }

    /**
     */
    public function test_it_refreshes_cache(): void
    {
        // Create old cache
        file_put_contents($this->cacheFile, '<?php return [];');
        $mtime1 = filemtime($this->cacheFile);

        sleep(1);

        $this->commandTester->execute(['--refresh' => true]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(0, $statusCode);
        $this->assertStringContainsString('Refreshing Discovery Cache', $output);
        $this->assertStringContainsString('Cache refreshed successfully', $output);

        $mtime2 = filemtime($this->cacheFile);
        $this->assertGreaterThan($mtime1, $mtime2);
    }

    /**
     */
    public function test_it_shows_discovered_bindings(): void
    {
        $this->commandTester->execute(['--stats' => true]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Discovered Bindings', $output);
        $this->assertStringContainsString('Interface', $output);
        $this->assertStringContainsString('Implementation', $output);
    }

    /**
     */
    public function test_it_shows_cache_status(): void
    {
        $this->commandTester->execute(['--stats' => true]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Cache Exists', $output);
        $this->assertStringContainsString('Cache Valid', $output);
        $this->assertStringContainsString('Environment', $output);
    }

    /**
     */
    public function test_it_handles_clear_when_no_cache_exists(): void
    {
        $this->assertFileDoesNotExist($this->cacheFile);

        $this->commandTester->execute(['--clear' => true]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(0, $statusCode);
        $this->assertStringContainsString('No cache file to clear', $output);
    }
}
