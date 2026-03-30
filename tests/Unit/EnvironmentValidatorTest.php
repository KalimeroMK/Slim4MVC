<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Validation\ConfigurationException;
use App\Modules\Core\Infrastructure\Validation\EnvironmentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\Core\Infrastructure\Validation\EnvironmentValidator
 * @covers \App\Modules\Core\Infrastructure\Validation\ConfigurationException
 */
final class EnvironmentValidatorTest extends TestCase
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

    public function test_validate_passes_with_valid_configuration(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'local',
        ];

        // Should not throw
        EnvironmentValidator::validate();

        $this->assertTrue(true);
    }

    public function test_validate_throws_on_missing_jwt_secret(): void
    {
        $_ENV = [
            'DB_HOST' => 'localhost',
            'APP_ENV' => 'local',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET is required');

        EnvironmentValidator::validate();
    }

    public function test_validate_throws_on_short_jwt_secret(): void
    {
        $_ENV = [
            'JWT_SECRET' => 'short',
            'DB_HOST' => 'localhost',
            'APP_ENV' => 'local',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET must be at least 32 characters');

        EnvironmentValidator::validate();
    }

    public function test_validate_throws_on_weak_jwt_secret(): void
    {
        $_ENV = [
            'JWT_SECRET' => 'secret',
            'DB_HOST' => 'localhost',
            'APP_ENV' => 'local',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('default/weak value');

        EnvironmentValidator::validate();
    }

    public function test_validate_throws_on_invalid_app_env(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'APP_ENV' => 'invalid',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('APP_ENV must be one of');

        EnvironmentValidator::validate();
    }

    public function test_validate_checks_production_requirements(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'APP_ENV' => 'production',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('REDIS_HOST is required in production');

        EnvironmentValidator::validate();
    }

    public function test_validate_passes_production_with_all_requirements(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'production',
            'REDIS_HOST' => 'redis',
            'CACHE_DRIVER' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'MAIL_HOST' => 'smtp.example.com',
            'MAIL_USERNAME' => 'user@example.com',
            'MAIL_PASSWORD' => 'password',
        ];

        // Should not throw
        EnvironmentValidator::validate();

        $this->assertTrue(true);
    }

    public function test_getSummary_returns_expected_structure(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_CONNECTION' => 'mysql',
            'APP_ENV' => 'local',
            'CACHE_DRIVER' => 'file',
            'SESSION_DRIVER' => 'file',
        ];

        $summary = EnvironmentValidator::getSummary();

        $this->assertArrayHasKey('environment', $summary);
        $this->assertArrayHasKey('is_production', $summary);
        $this->assertArrayHasKey('jwt_configured', $summary);
        $this->assertArrayHasKey('jwt_secret_length', $summary);
        $this->assertArrayHasKey('db_connection', $summary);
        $this->assertArrayHasKey('warnings', $summary);

        $this->assertEquals('local', $summary['environment']);
        $this->assertFalse($summary['is_production']);
        $this->assertTrue($summary['jwt_configured']);
        $this->assertEquals(32, $summary['jwt_secret_length']);
    }

    public function test_getWarnings_returns_empty_array_when_no_warnings(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('aX9!', 16), // Strong secret with numbers and special chars
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'local',
        ];

        EnvironmentValidator::validate();
        $warnings = EnvironmentValidator::getWarnings();

        $this->assertIsArray($warnings);
        $this->assertEmpty($warnings);
    }

    public function test_assertJwtSecret_throws_on_empty_secret(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET cannot be empty');

        EnvironmentValidator::assertJwtSecret('');
    }

    public function test_assertJwtSecret_throws_on_short_secret(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET must be at least 32 characters');

        EnvironmentValidator::assertJwtSecret('short_secret');
    }

    public function test_assertJwtSecret_passes_with_valid_secret(): void
    {
        // Should not throw
        EnvironmentValidator::assertJwtSecret(str_repeat('a', 32));

        $this->assertTrue(true);
    }

    public function test_isProduction_returns_true_for_production(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $this->assertTrue(EnvironmentValidator::isProduction());
    }

    public function test_isProduction_returns_true_for_staging(): void
    {
        $_ENV['APP_ENV'] = 'staging';
        $this->assertTrue(EnvironmentValidator::isProduction());
    }

    public function test_isProduction_returns_false_for_local(): void
    {
        $_ENV['APP_ENV'] = 'local';
        $this->assertFalse(EnvironmentValidator::isProduction());
    }

    public function test_required_throws_when_key_missing(): void
    {
        unset($_ENV['TEST_KEY']);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('TEST_KEY is required');

        EnvironmentValidator::required('TEST_KEY');
    }

    public function test_required_passes_when_key_exists(): void
    {
        $_ENV['TEST_KEY'] = 'value';

        // Should not throw
        EnvironmentValidator::required('TEST_KEY');

        $this->assertTrue(true);
    }

    public function test_configuration_exception_getErrors(): void
    {
        $errors = ['Error 1', 'Error 2'];
        $configurationException = new ConfigurationException($errors);

        $this->assertSame($errors, $configurationException->getErrors());
    }

    public function test_configuration_exception_getSummary(): void
    {
        $errors = ['JWT_SECRET is required'];
        $configurationException = new ConfigurationException($errors);

        $summary = $configurationException->getSummary();

        $this->assertArrayHasKey('error', $summary);
        $this->assertArrayHasKey('message', $summary);
        $this->assertArrayHasKey('errors', $summary);
        $this->assertEquals('Configuration Error', $summary['error']);
        $this->assertEquals(['JWT_SECRET is required'], $summary['errors']);
    }

    public function test_configuration_exception_getDetailedMessage(): void
    {
        $errors = ['JWT_SECRET is required'];
        $configurationException = new ConfigurationException($errors);

        $message = $configurationException->getDetailedMessage();

        $this->assertStringContainsString('CONFIGURATION VALIDATION FAILED', $message);
        $this->assertStringContainsString('JWT_SECRET is required', $message);
    }
}
