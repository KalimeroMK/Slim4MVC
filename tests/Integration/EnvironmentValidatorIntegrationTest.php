<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Core\Infrastructure\Validation\ConfigurationException;
use App\Modules\Core\Infrastructure\Validation\EnvironmentValidator;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Environment Validator.
 * 
 * Tests the validator with real environment scenarios.
 * 
 * @covers \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator
 * @covers \App\Modules\Core\Infrastructure\Validation\ConfigurationException
 */
final class EnvironmentValidatorIntegrationTest extends TestCase
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
    public function test_it_validates_real_production_environment(): void
    {
        $_ENV = [
            'APP_ENV' => 'production',
            'APP_URL' => 'https://example.com',
            'JWT_SECRET' => bin2hex(random_bytes(32)),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'prod-db.example.com',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'production_db',
            'DB_USERNAME' => 'app_user',
            'DB_PASSWORD' => 'secure_password_123',
            'REDIS_HOST' => 'prod-redis.example.com',
            'REDIS_PORT' => '6379',
            'CACHE_DRIVER' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'MAIL_HOST' => 'smtp.example.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'noreply@example.com',
            'MAIL_PASSWORD' => 'mail_password',
            'MAIL_ENCRYPTION' => 'tls',
        ];

        // Should not throw
        EnvironmentValidator::validate();
        
        $this->assertTrue(EnvironmentValidator::isProduction());
        $summary = EnvironmentValidator::getSummary();
        $this->assertTrue($summary['is_production']);
        $this->assertTrue($summary['jwt_configured']);
    }

    /**
     */
    public function test_it_validates_real_local_environment(): void
    {
        $_ENV = [
            'APP_ENV' => 'local',
            'APP_URL' => 'http://localhost:81',
            'JWT_SECRET' => 'local-dev-secret-key-32-chars-long!!',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'slim_db',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'slim',
            'DB_USERNAME' => 'slim',
            'DB_PASSWORD' => 'secret',
        ];

        EnvironmentValidator::validate();
        
        $this->assertFalse(EnvironmentValidator::isProduction());
    }

    /**
     */
    public function test_it_detects_missing_critical_variables(): void
    {
        $_ENV = [
            'APP_ENV' => 'local',
            // Missing JWT_SECRET, DB_HOST, etc.
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET is required');

        EnvironmentValidator::validate();
    }

    /**
     */
    public function test_it_detects_weak_jwt_secret_in_production(): void
    {
        $_ENV = [
            'APP_ENV' => 'production',
            'JWT_SECRET' => 'secret', // Too weak!
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

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('default/weak value');

        EnvironmentValidator::validate();
    }

    /**
     */
    public function test_it_generates_detailed_error_message(): void
    {
        $_ENV = [
            'APP_ENV' => 'invalid_env',
        ];

        try {
            EnvironmentValidator::validate();
            $this->fail('Expected ConfigurationException');
        } catch (ConfigurationException $configurationException) {
            $detailed = $configurationException->getDetailedMessage();
            $this->assertStringContainsString('CONFIGURATION VALIDATION FAILED', $detailed);
            $this->assertStringContainsString('❌', $detailed);
            
            $summary = $configurationException->getSummary();
            $this->assertArrayHasKey('error', $summary);
            $this->assertArrayHasKey('errors', $summary);
        }
    }

    /**
     */
    public function test_it_validates_staging_environment(): void
    {
        $_ENV = [
            'APP_ENV' => 'staging',
            'JWT_SECRET' => bin2hex(random_bytes(32)),
            'DB_HOST' => 'staging-db',
            'DB_DATABASE' => 'staging_db',
            'DB_USERNAME' => 'staging',
            'DB_PASSWORD' => 'staging_pass',
            'REDIS_HOST' => 'staging-redis',
            'CACHE_DRIVER' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'MAIL_HOST' => 'smtp.staging.com',
            'MAIL_USERNAME' => 'staging@example.com',
            'MAIL_PASSWORD' => 'staging_pass',
        ];

        EnvironmentValidator::validate();
        
        // Staging is treated as production for validation purposes
        $this->assertTrue(EnvironmentValidator::isProduction());
    }

    /**
     */
    public function test_it_validates_testing_environment(): void
    {
        $_ENV = [
            'APP_ENV' => 'testing',
            'JWT_SECRET' => 'test-secret-key-32-chars-long!!!',
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test_db',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => 'password',
        ];

        EnvironmentValidator::validate();
        $this->assertFalse(EnvironmentValidator::isProduction());
    }

    /**
     */
    public function test_it_detects_jwt_secret_without_numbers_or_special_chars(): void
    {
        $_ENV = [
            'APP_ENV' => 'local',
            'JWT_SECRET' => 'onlylettersherethatislongenoughnow',
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
        ];

        EnvironmentValidator::validate();
        
        $warnings = EnvironmentValidator::getWarnings();
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString(
            'numbers and special characters',
            implode(' ', $warnings)
        );
    }

    /**
     */
    public function test_it_asserts_jwt_secret_directly(): void
    {
        // Valid secret
        EnvironmentValidator::assertJwtSecret(bin2hex(random_bytes(32)));
        $this->assertTrue(true); // No exception
    }

    /**
     */
    public function test_it_throws_when_asserting_empty_jwt_secret(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET cannot be empty');

        EnvironmentValidator::assertJwtSecret('');
    }

    /**
     */
    public function test_it_throws_when_asserting_short_jwt_secret(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('at least 32 characters');

        EnvironmentValidator::assertJwtSecret('short');
    }

    /**
     */
    public function test_it_requires_specific_key(): void
    {
        $_ENV = ['SOME_OTHER_KEY' => 'value'];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('REQUIRED_KEY is required');

        EnvironmentValidator::required('REQUIRED_KEY');
    }

    /**
     */
    public function test_it_passes_when_required_key_exists(): void
    {
        $_ENV = ['REQUIRED_KEY' => 'value'];

        // Should not throw
        EnvironmentValidator::required('REQUIRED_KEY');
        $this->assertTrue(true);
    }

    /**
     */
    public function test_it_handles_null_values_as_empty(): void
    {
        $_ENV = [
            'JWT_SECRET' => null,
            'APP_ENV' => 'local',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET is required');

        EnvironmentValidator::validate();
    }

    /**
     */
    public function test_it_handles_whitespace_only_values_as_empty(): void
    {
        $_ENV = [
            'JWT_SECRET' => '   ',
            'APP_ENV' => 'local',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET is required');

        EnvironmentValidator::validate();
    }

    /**
     */
    public function test_it_validates_development_environment(): void
    {
        $_ENV = [
            'APP_ENV' => 'development',
            'JWT_SECRET' => 'dev-secret-key-32-chars-long-for-use',
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'dev_db',
            'DB_USERNAME' => 'dev',
            'DB_PASSWORD' => 'dev',
        ];

        EnvironmentValidator::validate();
        $this->assertFalse(EnvironmentValidator::isProduction());
    }

    /**
     */
    public function test_it_accumulates_multiple_errors(): void
    {
        $_ENV = [
            'APP_ENV' => 'invalid',
            'JWT_SECRET' => 'short',
        ];

        try {
            EnvironmentValidator::validate();
            $this->fail('Expected ConfigurationException');
        } catch (ConfigurationException $configurationException) {
            $errors = $configurationException->getErrors();
            $this->assertGreaterThanOrEqual(2, count($errors));
            $this->assertStringContainsString('APP_ENV', $configurationException->getMessage());
        }
    }
}
