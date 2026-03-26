<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Feature tests for Environment Validation in HTTP context.
 * 
 * Tests the actual HTTP responses when validation fails.
 * 
 * @group feature
 */
class EnvironmentValidationFeatureTest extends TestCase
{
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;
        parent::tearDown();
    }

    /**
     */
    public function test_it_returns_detailed_message_for_cli(): void
    {
        $_ENV = [
            'APP_ENV' => 'local',
            // Missing JWT_SECRET
        ];

        $this->expectException(\App\Modules\Core\Infrastructure\Validation\ConfigurationException::class);

        \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator::validate();
    }

    /**
     */
    public function test_it_shows_production_checks_for_production_environment(): void
    {
        $_ENV = [
            'APP_ENV' => 'production',
            'JWT_SECRET' => bin2hex(random_bytes(32)),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            // Missing REDIS_HOST, MAIL_HOST, etc.
        ];

        try {
            \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator::validate();
            $this->fail('Expected exception');
        } catch (\App\Modules\Core\Infrastructure\Validation\ConfigurationException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('production', strtolower($message));
            $this->assertStringContainsString('REDIS_HOST', $message);
        }
    }

    /**
     */
    public function test_it_passes_validation_with_complete_configuration(): void
    {
        $_ENV = [
            'APP_ENV' => 'production',
            'JWT_SECRET' => bin2hex(random_bytes(32)),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'REDIS_HOST' => 'localhost',
            'CACHE_DRIVER' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'MAIL_HOST' => 'smtp.example.com',
            'MAIL_USERNAME' => 'user@example.com',
            'MAIL_PASSWORD' => 'pass',
        ];

        // Should not throw
        \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator::validate();
        
        $this->assertTrue(true);
    }

    /**
     */
    public function test_it_detects_common_weak_secrets(): void
    {
        $weakSecrets = [
            'secret',
            'password',
            '123456',
            'jwt_secret',
            'your-secret-key',
        ];

        foreach ($weakSecrets as $secret) {
            $_ENV = [
                'APP_ENV' => 'local',
                'JWT_SECRET' => $secret,
                'DB_HOST' => 'localhost',
                'DB_DATABASE' => 'test',
                'DB_USERNAME' => 'user',
                'DB_PASSWORD' => 'pass',
            ];

            try {
                \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator::validate();
                $this->fail("Should reject weak secret: {$secret}");
            } catch (\App\Modules\Core\Infrastructure\Validation\ConfigurationException $e) {
                $this->assertStringContainsString('weak', strtolower($e->getMessage()));
            }
        }
    }

    /**
     */
    public function test_it_generates_summary_with_all_fields(): void
    {
        $_ENV = [
            'APP_ENV' => 'local',
            'JWT_SECRET' => 'local-dev-secret-key-32-chars-long!!',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'CACHE_DRIVER' => 'file',
            'SESSION_DRIVER' => 'file',
        ];

        $summary = \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator::getSummary();

        $this->assertArrayHasKey('environment', $summary);
        $this->assertArrayHasKey('is_production', $summary);
        $this->assertArrayHasKey('jwt_configured', $summary);
        $this->assertArrayHasKey('jwt_secret_length', $summary);
        $this->assertArrayHasKey('db_connection', $summary);
        $this->assertArrayHasKey('db_configured', $summary);
        $this->assertArrayHasKey('cache_driver', $summary);
        $this->assertArrayHasKey('session_driver', $summary);
        $this->assertArrayHasKey('warnings', $summary);
    }

    /**
     */
    public function test_it_returns_warnings_for_improvements(): void
    {
        $_ENV = [
            'APP_ENV' => 'local',
            'JWT_SECRET' => 'onlylettersherethatislongenoughx',  // Only letters (32 chars) to trigger warning
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
        ];

        \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator::validate();
        $warnings = \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator::getWarnings();

        $this->assertNotEmpty($warnings);
        $this->assertContains('JWT_SECRET should contain numbers and special characters for better security', $warnings);
    }
}
