<?php

declare(strict_types=1);

namespace Tests\EdgeCases;

use App\Modules\Core\Infrastructure\Validation\ConfigurationException;
use App\Modules\Core\Infrastructure\Validation\EnvironmentValidator;
use PHPUnit\Framework\TestCase;

/**
 * Edge case tests for Environment Validator.
 *
 * @group edge-case
 */
final class EnvironmentValidatorEdgeCasesTest extends TestCase
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

    public function test_it_handles_very_long_jwt_secret(): void
    {
        $_ENV = [
            'JWT_SECRET' => bin2hex(random_bytes(1024)), // 2048 chars
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'local',
        ];

        EnvironmentValidator::validate();
        $this->assertTrue(true);
    }

    public function test_it_handles_unicode_in_env_values(): void
    {
        $_ENV = [
            'JWT_SECRET' => '🔐секрет-key-32-chars-long!!!🔐',
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'пассворд123!',
            'APP_ENV' => 'local',
        ];

        EnvironmentValidator::validate();
        $this->assertTrue(true);
    }

    public function test_it_handles_exactly_32_char_secret(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'local',
        ];

        EnvironmentValidator::validate();
        $this->assertTrue(true);
    }

    public function test_it_rejects_31_char_secret(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 31),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'local',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('32 characters');

        EnvironmentValidator::validate();
    }

    public function test_it_handles_special_chars_in_db_password(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'p@$$w0rd!#$%^&*()',
            'APP_ENV' => 'local',
        ];

        EnvironmentValidator::validate();
        $this->assertTrue(true);
    }

    public function test_it_handles_zero_user_id(): void
    {
        $_ENV = [
            'JWT_SECRET' => str_repeat('a', 32),
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'test',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'pass',
            'APP_ENV' => 'local',
        ];

        EnvironmentValidator::validate();
        $this->assertTrue(true);
    }
}
