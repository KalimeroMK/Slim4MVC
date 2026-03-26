<?php

declare(strict_types=1);

namespace Tests\EdgeCases;

use App\Modules\Core\Infrastructure\Support\AdvancedJwtService;
use PHPUnit\Framework\TestCase;

/**
 * Edge case tests for Advanced JWT Service.
 * 
 * @group edge-case
 */
class JwtServiceEdgeCasesTest extends TestCase
{
    private string $validSecret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validSecret = bin2hex(random_bytes(32));
    }

    /**
     */
    public function test_it_handles_zero_user_id(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(userId: 0);

        $payload = $service->decode($token);
        $this->assertEquals('0', $payload->sub);
    }

    /**
     */
    public function test_it_handles_negative_user_id(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(userId: -1);

        $payload = $service->decode($token);
        $this->assertEquals('-1', $payload->sub);
    }

    /**
     */
    public function test_it_handles_empty_claims_array(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(userId: 1, claims: []);

        $payload = $service->decode($token);
        $this->assertEquals('1', $payload->sub);
    }

    /**
     */
    public function test_it_handles_unicode_in_claims(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(userId: 1, claims: [
            'message' => 'Hello 世界 🌍',
            'name' => 'José García'
        ]);

        $payload = $service->decode($token);
        $this->assertEquals('Hello 世界 🌍', $payload->message);
        $this->assertEquals('José García', $payload->name);
    }

    /**
     */
    public function test_it_handles_very_large_ttl(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(userId: 1, ttl: 31536000); // 1 year

        $payload = $service->decode($token);
        $this->assertGreaterThan(time() + 30000000, $payload->exp);
    }

    /**
     */
    public function test_it_handles_array_claims(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $token = $service->generateAccessToken(userId: 1, claims: [
            'roles' => ['admin', 'user', 'editor'],
            'permissions' => ['read', 'write', 'delete']
        ]);

        $payload = $service->decode($token);
        $this->assertEquals(['admin', 'user', 'editor'], (array) $payload->roles);
    }

    /**
     */
    public function test_it_rejects_base64_garbage(): void
    {
        $service = new AdvancedJwtService($this->validSecret);

        $garbageTokens = [
            '!!!.!!!.!!!',
            'a.b.c',
            str_repeat('=', 100),
        ];

        foreach ($garbageTokens as $token) {
            $this->assertFalse($service->verify($token), "Token should be invalid: {$token}");
        }
    }

    /**
     */
    public function test_it_handles_concurrent_token_generation(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $tokens = [];

        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $service->generateAccessToken(userId: $i);
        }

        // All should be unique
        $this->assertCount(100, array_unique($tokens));

        // All should be valid
        foreach ($tokens as $token) {
            $this->assertTrue($service->verify($token));
        }
    }

    /**
     */
    public function test_it_handles_very_long_claim_values(): void
    {
        $service = new AdvancedJwtService($this->validSecret);
        $longValue = str_repeat('a', 10000);

        $token = $service->generateAccessToken(userId: 1, claims: [
            'long_value' => $longValue
        ]);

        $payload = $service->decode($token);
        $this->assertEquals($longValue, $payload->long_value);
    }

    /**
     */
    public function test_it_handles_null_algorithm_gracefully(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported algorithm');

        new AdvancedJwtService($this->validSecret, 'NONE');
    }

    /**
     */
    public function test_it_gets_info_for_malformed_tokens(): void
    {
        $service = new AdvancedJwtService($this->validSecret);

        $malformedTokens = ['', 'just-one-part', 'part.one'];

        foreach ($malformedTokens as $token) {
            $info = $service->getTokenInfo($token);
            $this->assertFalse($info['valid'], "Token should be invalid");
            $this->assertArrayHasKey('error', $info);
        }
    }
}
