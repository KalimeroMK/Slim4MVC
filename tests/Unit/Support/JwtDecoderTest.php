<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Modules\Core\Infrastructure\Support\JwtDecoder;
use App\Modules\Core\Infrastructure\Support\JwtEncoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(JwtDecoder::class)]
final class JwtDecoderTest extends TestCase
{
    private const string SECRET = 'a-test-secret-that-is-at-least-32-chars';

    private JwtDecoder $decoder;

    private JwtEncoder $encoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoder = new JwtDecoder();
        $this->encoder = new JwtEncoder();
    }

    public function test_it_decodes_a_valid_token(): void
    {
        $token = $this->encoder->encode(
            ['sub' => '42', 'exp' => time() + 3600],
            self::SECRET
        );

        $payload = $this->decoder->decode($token, self::SECRET);

        $this->assertSame('42', $payload->sub);
    }

    public function test_it_rejects_a_token_without_an_exp_claim(): void
    {
        // Such a token would authenticate forever.
        $token = $this->encoder->encode(['sub' => '42'], self::SECRET);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing the exp claim');

        $this->decoder->decode($token, self::SECRET);
    }

    public function test_it_rejects_a_non_numeric_exp_claim(): void
    {
        $token = $this->encoder->encode(['sub' => '42', 'exp' => 'never'], self::SECRET);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be a timestamp');

        $this->decoder->decode($token, self::SECRET);
    }

    public function test_it_rejects_an_expired_token(): void
    {
        $token = $this->encoder->encode(['sub' => '42', 'exp' => time() - 1], self::SECRET);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expired');

        $this->decoder->decode($token, self::SECRET);
    }

    public function test_it_rejects_a_token_that_is_not_yet_valid(): void
    {
        $token = $this->encoder->encode(
            ['sub' => '42', 'exp' => time() + 3600, 'nbf' => time() + 600],
            self::SECRET
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not yet valid');

        $this->decoder->decode($token, self::SECRET);
    }

    public function test_it_rejects_a_token_signed_with_another_secret(): void
    {
        $token = $this->encoder->encode(
            ['sub' => '42', 'exp' => time() + 3600],
            'a-different-secret-of-at-least-32-chars'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('signature verification failed');

        $this->decoder->decode($token, self::SECRET);
    }

    public function test_it_rejects_a_tampered_payload(): void
    {
        $token = $this->encoder->encode(['sub' => '42', 'exp' => time() + 3600], self::SECRET);
        [$header, , $signature] = explode('.', $token);

        $forged = $this->base64UrlEncode((string) json_encode(['sub' => '1', 'exp' => time() + 3600]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('signature verification failed');

        $this->decoder->decode(sprintf('%s.%s.%s', $header, $forged, $signature), self::SECRET);
    }

    public function test_it_rejects_the_none_algorithm(): void
    {
        $header = $this->base64UrlEncode((string) json_encode(['typ' => 'JWT', 'alg' => 'none']));
        $payload = $this->base64UrlEncode((string) json_encode(['sub' => '1', 'exp' => time() + 3600]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('algorithm mismatch');

        $this->decoder->decode($header.'.'.$payload.'.', self::SECRET);
    }

    public function test_it_rejects_a_header_advertising_a_different_algorithm(): void
    {
        $header = $this->base64UrlEncode((string) json_encode(['typ' => 'JWT', 'alg' => 'HS512']));
        $body = ['sub' => '1', 'exp' => time() + 3600];
        $payload = $this->base64UrlEncode((string) json_encode($body));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', $header.'.'.$payload, self::SECRET, true)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('algorithm mismatch');

        $this->decoder->decode(sprintf('%s.%s.%s', $header, $payload, $signature), self::SECRET);
    }

    public function test_it_rejects_a_malformed_token(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JWT token format');

        $this->decoder->decode('only.two', self::SECRET);
    }

    public function test_it_rejects_an_empty_secret(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT secret cannot be empty');

        $this->decoder->decode('a.b.c', '');
    }

    public function test_it_rejects_an_unsupported_requested_algorithm(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported algorithm');

        $this->decoder->decode('a.b.c', self::SECRET, 'RS256');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
