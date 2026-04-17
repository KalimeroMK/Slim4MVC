<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Modules\Core\Infrastructure\Support\JwtService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \App\Modules\Core\Infrastructure\Support\JwtService
 */
final class JwtServiceSecurityTest extends TestCase
{
    private const VALID_SECRET = 'this-is-a-very-long-secret-key-that-is-32-chars!';

    public function test_constructor_requires_explicit_secret(): void
    {
        $service = new JwtService(self::VALID_SECRET);

        $this->assertSame(self::VALID_SECRET, $service->getSecret());
    }

    public function test_throws_when_secret_is_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT secret cannot be empty');

        new JwtService('');
    }

    public function test_throws_when_secret_is_too_short(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT secret must be at least 32 characters');

        new JwtService('short');
    }

    public function test_throws_when_secret_is_zero_string(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT secret cannot be empty');

        new JwtService('0');
    }

    public function test_encodes_and_decodes_payload(): void
    {
        $service = new JwtService(self::VALID_SECRET);
        $payload = ['user_id' => 123, 'email' => 'test@example.com'];

        $token = $service->encode($payload, 3600);
        $decoded = $service->decode($token);

        $this->assertEquals(123, $decoded->user_id);
        $this->assertEquals('test@example.com', $decoded->email);
    }

    public function test_adds_expiration_when_provided(): void
    {
        $service = new JwtService(self::VALID_SECRET);
        $payload = ['user_id' => 1];

        $token = $service->encode($payload, 3600);
        $decoded = $service->decode($token);

        $this->assertTrue(isset($decoded->exp));
        $this->assertGreaterThan(time(), $decoded->exp);
    }

    public function test_does_not_use_env_fallback(): void
    {
        // Ensure $_ENV does NOT affect the service when a secret is passed
        $_ENV['JWT_SECRET'] = 'env-secret-that-should-not-be-used';

        $service = new JwtService(self::VALID_SECRET);
        $this->assertSame(self::VALID_SECRET, $service->getSecret());

        // Ensure it fails without explicit secret
        $this->expectException(TypeError::class);
        new JwtService();
    }
}
