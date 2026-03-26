<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Core\Infrastructure\Support\AdvancedJwtService;
use App\Modules\Core\Infrastructure\Support\Token\TokenPair;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Advanced JWT Service.
 * 
 * Tests the complete JWT flow with real token generation and validation.
 * 
 * @covers \App\Modules\Core\Infrastructure\Support\AdvancedJwtService
 * @covers \App\Modules\Core\Infrastructure\Support\Token\TokenPair
 */
class JwtServiceIntegrationTest extends TestCase
{
    private string $validSecret;
    private AdvancedJwtService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validSecret = bin2hex(random_bytes(32));
        $this->service = new AdvancedJwtService($this->validSecret);
    }

    /**
     */
    public function test_it_generates_and_validates_access_token(): void
    {
        $token = $this->service->generateAccessToken(
            userId: 123,
            claims: ['role' => 'admin', 'permissions' => ['read', 'write']]
        );

        $this->assertIsString($token);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // Verify token
        $this->assertTrue($this->service->verify($token));

        // Decode and check claims
        $payload = $this->service->decode($token);
        $this->assertEquals('123', $payload->sub);
        $this->assertEquals('access', $payload->type);
        $this->assertEquals('admin', $payload->role);
    }

    /**
     */
    public function test_it_generates_token_pair_with_refresh_token(): void
    {
        $tokenPair = $this->service->generateRefreshToken(userId: 456);

        $this->assertInstanceOf(TokenPair::class, $tokenPair);
        $this->assertNotEmpty($tokenPair->getAccessToken());
        $this->assertNotEmpty($tokenPair->getRefreshToken());
        $this->assertGreaterThan(0, $tokenPair->getExpiresIn());

        // Verify access token
        $this->assertTrue($this->service->verify($tokenPair->getAccessToken()));

        // Verify refresh token
        $this->assertTrue($this->service->verify($tokenPair->getRefreshToken()));
    }

    /**
     */
    public function test_it_encodes_and_decodes_token_with_issuer_and_audience(): void
    {
        $service = new AdvancedJwtService(
            secret: $this->validSecret,
            issuer: 'my-app',
            audience: 'my-api',
            defaultTtl: 3600
        );

        $token = $service->generateAccessToken(userId: 789);
        $payload = $service->decode($token, validateIssuer: true, validateAudience: true);

        $this->assertEquals('my-app', $payload->iss);
        $this->assertEquals('my-api', $payload->aud);
    }

    /**
     */
    public function test_it_rejects_token_with_wrong_issuer(): void
    {
        $service1 = new AdvancedJwtService(
            secret: $this->validSecret,
            issuer: 'app-1'
        );

        $service2 = new AdvancedJwtService(
            secret: $this->validSecret,
            issuer: 'app-2'
        );

        $token = $service1->generateAccessToken(userId: 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid token issuer');

        $service2->decode($token, validateIssuer: true);
    }

    /**
     */
    public function test_it_rejects_token_with_wrong_audience(): void
    {
        $service1 = new AdvancedJwtService(
            secret: $this->validSecret,
            audience: 'api-1'
        );

        $service2 = new AdvancedJwtService(
            secret: $this->validSecret,
            audience: 'api-2'
        );

        $token = $service1->generateAccessToken(userId: 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid token audience');

        $service2->decode($token, validateAudience: true);
    }

    /**
     */
    public function test_it_rejects_expired_token(): void
    {
        // Create token that expires immediately
        $token = $this->service->generateAccessToken(userId: 1, ttl: -1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expired');

        $this->service->decode($token);
    }

    /**
     */
    public function test_it_rejects_tampered_token(): void
    {
        $token = $this->service->generateAccessToken(userId: 1);
        
        // Tamper with the token
        $parts = explode('.', $token);
        $parts[1] = base64_encode(json_encode(['sub' => '999']));
        $tamperedToken = implode('.', $parts);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('signature verification failed');

        $this->service->decode($tamperedToken);
    }

    /**
     */
    public function test_it_gets_token_info(): void
    {
        $token = $this->service->generateAccessToken(
            userId: 123,
            claims: ['role' => 'user'],
            ttl: 3600
        );

        $info = $this->service->getTokenInfo($token);

        $this->assertTrue($info['valid']);
        $this->assertEquals('access', $info['type']);
        $this->assertEquals('123', $info['subject']);
        $this->assertFalse($info['is_expired']);
        $this->assertNotNull($info['issued_at']);
        $this->assertNotNull($info['expires_at']);
        $this->assertNotNull($info['jwt_id']);
    }

    /**
     */
    public function test_it_gets_error_info_for_invalid_token(): void
    {
        $info = $this->service->getTokenInfo('invalid.token.here');

        $this->assertFalse($info['valid']);
        $this->assertArrayHasKey('error', $info);
    }

    /**
     */
    public function test_it_rejects_refresh_token_as_access_token(): void
    {
        $tokenPair = $this->service->generateRefreshToken(userId: 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid token type');

        $this->service->rotateRefreshToken($tokenPair->getAccessToken());
    }

    /**
     */
    public function test_it_detects_refresh_token_reuse(): void
    {
        $tokenPair = $this->service->generateRefreshToken(userId: 1);

        // First rotation should work
        $newPair = $this->service->rotateRefreshToken($tokenPair->getRefreshToken());
        $this->assertInstanceOf(TokenPair::class, $newPair);

        // Second rotation with same token should fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('revoked or already used');

        $this->service->rotateRefreshToken($tokenPair->getRefreshToken());
    }

    /**
     */
    public function test_it_generates_unique_jwt_ids(): void
    {
        $token1 = $this->service->generateAccessToken(userId: 1);
        $token2 = $this->service->generateAccessToken(userId: 1);

        $payload1 = $this->service->decode($token1);
        $payload2 = $this->service->decode($token2);

        $this->assertNotEquals($payload1->jti, $payload2->jti);
    }

    /**
     */
    public function test_it_supports_string_user_ids(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $token = $this->service->generateAccessToken(userId: $uuid);

        $payload = $this->service->decode($token);
        $this->assertEquals($uuid, $payload->sub);
    }

    /**
     */
    public function test_it_rejects_token_with_wrong_signature(): void
    {
        $token = $this->service->generateAccessToken(userId: 1);

        // Create new service with different secret
        $otherService = new AdvancedJwtService(bin2hex(random_bytes(32)));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('signature verification failed');

        $otherService->decode($token);
    }

    /**
     */
    public function test_it_supports_different_algorithms(): void
    {
        $service256 = new AdvancedJwtService($this->validSecret, 'HS256');
        $service384 = new AdvancedJwtService($this->validSecret, 'HS384');
        $service512 = new AdvancedJwtService($this->validSecret, 'HS512');

        $token256 = $service256->generateAccessToken(userId: 1);
        $token384 = $service384->generateAccessToken(userId: 1);
        $token512 = $service512->generateAccessToken(userId: 1);

        $this->assertTrue($service256->verify($token256));
        $this->assertTrue($service384->verify($token384));
        $this->assertTrue($service512->verify($token512));
    }

    /**
     */
    public function test_it_converts_token_pair_to_array(): void
    {
        $tokenPair = $this->service->generateRefreshToken(userId: 1);
        $array = $tokenPair->toArray();

        $this->assertArrayHasKey('access_token', $array);
        $this->assertArrayHasKey('refresh_token', $array);
        $this->assertArrayHasKey('token_type', $array);
        $this->assertArrayHasKey('expires_in', $array);
        $this->assertEquals('Bearer', $array['token_type']);
    }

    /**
     */
    public function test_it_validates_not_before_claim(): void
    {
        // Create a token with nbf in the future (should fail)
        $encoder = new \App\Modules\Core\Infrastructure\Support\JwtEncoder();
        $token = $encoder->encode([
            'sub' => '1',
            'iat' => time(),
            'exp' => time() + 3600,
            'nbf' => time() + 100, // Not valid yet
        ], $this->validSecret);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not yet valid');

        $this->service->decode($token);
    }

    /**
     */
    public function test_it_rejects_malformed_token(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JWT token format');

        $this->service->decode('invalid-token');
    }

    /**
     */
    public function test_it_rejects_empty_token(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JWT token format');

        $this->service->decode('');
    }
}
