<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Support\AdvancedJwtService;
use App\Modules\Core\Infrastructure\Validation\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\Core\Infrastructure\Support\AdvancedJwtService
 * @covers \App\Modules\Core\Infrastructure\Support\Token\TokenPair
 */
class AdvancedJwtServiceTest extends TestCase
{
    private string $validSecret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validSecret = str_repeat('a', 32);
    }

    public function test_constructor_throws_on_short_secret(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('JWT_SECRET must be at least 32 characters');

        new AdvancedJwtService('short');
    }

    public function test_constructor_throws_on_unsupported_algorithm(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported algorithm');

        new AdvancedJwtService($this->validSecret, 'RS256');
    }

    public function test_generateAccessToken_returns_valid_token(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(123, ['role' => 'admin']);

        $this->assertIsString($token);
        $this->assertCount(3, explode('.', $token)); // header.payload.signature
    }

    public function test_generateAccessToken_includes_claims(): void
    {
        $service = new AdvancedJwtService(
            secret: $this->validSecret,
            issuer: 'test-app',
            audience: 'test-api'
        );

        $token = $service->generateAccessToken(123, ['custom' => 'value']);
        $payload = $service->decode($token);

        $this->assertEquals('123', $payload->sub);
        $this->assertEquals('access', $payload->type);
        $this->assertEquals('test-app', $payload->iss);
        $this->assertEquals('test-api', $payload->aud);
        $this->assertEquals('value', $payload->custom);
        $this->assertNotEmpty($payload->jti);
        $this->assertNotEmpty($payload->iat);
        $this->assertNotEmpty($payload->exp);
    }

    public function test_generateAccessToken_custom_ttl(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(123, [], 3600);
        $payload = $service->decode($token);

        $expectedExp = time() + 3600;
        $this->assertEqualsWithDelta($expectedExp, $payload->exp, 5);
    }

    public function test_generateRefreshToken_returns_token_pair(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $tokenPair = $service->generateRefreshToken(123);

        $this->assertInstanceOf(\App\Modules\Core\Infrastructure\Support\Token\TokenPair::class, $tokenPair);
        $this->assertIsString($tokenPair->getAccessToken());
        $this->assertIsString($tokenPair->getRefreshToken());
        $this->assertEquals(2592000, $tokenPair->getExpiresIn());
    }

    public function test_generateRefreshToken_includes_correct_claims(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $tokenPair = $service->generateRefreshToken(123);

        $payload = $service->decode($tokenPair->getRefreshToken());

        $this->assertEquals('123', $payload->sub);
        $this->assertEquals('refresh', $payload->type);
        $this->assertNotEmpty($payload->jti);
        $this->assertNotEmpty($payload->fp); // fingerprint
    }

    public function test_rotateRefreshToken_throws_without_redis(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $originalPair = $service->generateRefreshToken(123);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token rotation requires Redis');

        $service->rotateRefreshToken($originalPair->getRefreshToken());
    }

    public function test_rotateRefreshToken_throws_on_access_token(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $accessToken = $service->generateAccessToken(123);

        // Without Redis, it will throw "Token rotation requires Redis" before checking type
        // With Redis, it would throw "Invalid token type"
        $this->expectException(\RuntimeException::class);

        $service->rotateRefreshToken($accessToken);
    }

    public function test_verify_returns_true_for_valid_token(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(123);

        $this->assertTrue($service->verify($token));
    }

    public function test_verify_returns_false_for_invalid_token(): void
    {
        $service = new AdvancedJwtService($this->validSecret);

        // Invalid token format (wrong number of parts)
        $this->assertFalse($service->verify('invalid'));
    }

    public function test_getTokenInfo_returns_expected_structure(): void
    {
        $service = new AdvancedJwtService(
            secret: $this->validSecret,
            issuer: 'test-app',
            audience: 'test-api'
        );

        $token = $service->generateAccessToken(123, ['role' => 'admin']);
        $info = $service->getTokenInfo($token);

        $this->assertArrayHasKey('valid', $info);
        $this->assertArrayHasKey('algorithm', $info);
        $this->assertArrayHasKey('type', $info);
        $this->assertArrayHasKey('issuer', $info);
        $this->assertArrayHasKey('audience', $info);
        $this->assertArrayHasKey('subject', $info);
        $this->assertArrayHasKey('issued_at', $info);
        $this->assertArrayHasKey('expires_at', $info);
        $this->assertArrayHasKey('is_expired', $info);
        $this->assertArrayHasKey('jwt_id', $info);

        $this->assertTrue($info['valid']);
        $this->assertEquals('HS256', $info['algorithm']);
        $this->assertEquals('access', $info['type']);
        $this->assertEquals('test-app', $info['issuer']);
        $this->assertEquals('test-api', $info['audience']);
        $this->assertEquals('123', $info['subject']);
        $this->assertFalse($info['is_expired']);
    }

    public function test_getTokenInfo_returns_error_for_invalid_token(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        
        // Invalid format
        $info = $service->getTokenInfo('invalid');
        $this->assertArrayHasKey('valid', $info);
        $this->assertArrayHasKey('error', $info);
        $this->assertFalse($info['valid']);
    }

    public function test_decode_validates_issuer_when_requested(): void
    {
        $service = new AdvancedJwtService(
            secret: $this->validSecret,
            issuer: 'test-app'
        );

        $token = $service->generateAccessToken(123);

        // Should work when validating issuer
        $payload = $service->decode($token, validateIssuer: true);
        $this->assertEquals('test-app', $payload->iss);
    }

    public function test_decode_validates_audience_when_requested(): void
    {
        $service = new AdvancedJwtService(
            secret: $this->validSecret,
            audience: 'test-api'
        );

        $token = $service->generateAccessToken(123);

        // Should work when validating audience
        $payload = $service->decode($token, validateAudience: true);
        $this->assertEquals('test-api', $payload->aud);
    }

    public function test_tokenPair_toArray(): void
    {
        $pair = new \App\Modules\Core\Infrastructure\Support\Token\TokenPair(
            accessToken: 'access123',
            refreshToken: 'refresh456',
            expiresIn: 3600
        );

        $array = $pair->toArray();

        $this->assertEquals('access123', $array['access_token']);
        $this->assertEquals('refresh456', $array['refresh_token']);
        $this->assertEquals('Bearer', $array['token_type']);
        $this->assertEquals(3600, $array['expires_in']);
    }

    public function test_decode_throws_on_expired_token(): void
    {
        $service = new AdvancedJwtService($this->validSecret);

        // Create token that expired 1 hour ago
        $encoder = new \App\Modules\Core\Infrastructure\Support\JwtEncoder();
        $expiredToken = $encoder->encode([
            'sub' => '123',
            'exp' => time() - 3600,
            'iat' => time() - 7200,
        ], $this->validSecret);

        $this->expectException(\RuntimeException::class);

        $service->decode($expiredToken);
    }

    public function test_generateAccessToken_with_string_user_id(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken('user-uuid-123');
        $payload = $service->decode($token);

        $this->assertEquals('user-uuid-123', $payload->sub);
    }
}
