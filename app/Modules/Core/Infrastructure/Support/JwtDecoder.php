<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use RuntimeException;
use stdClass;

/**
 * JWT Token Decoder
 *
 * Decodes and validates JWT tokens using HMAC-SHA256 algorithm.
 * Implements RFC 7519 JSON Web Token standard.
 */
class JwtDecoder
{
    /**
     * Decode and validate a JWT token.
     *
     * @param  string  $token  The JWT token to decode
     * @param  string  $secret  The secret key for verification
     * @param  string  $algorithm  The algorithm to use (default: HS256)
     * @return stdClass The decoded payload
     *
     * @throws RuntimeException If token is invalid, expired, or verification fails
     */
    public function decode(string $token, string $secret, string $algorithm = 'HS256'): stdClass
    {
        if ($secret === '' || $secret === '0') {
            throw new RuntimeException('JWT secret cannot be empty');
        }

        if ($algorithm !== 'HS256') {
            throw new RuntimeException("Unsupported algorithm: {$algorithm}. Only HS256 is supported.");
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWT token format');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Decode header
        $header = json_decode($this->base64UrlDecode($headerEncoded), false, 512, JSON_THROW_ON_ERROR);

        if (! isset($header->alg) || $header->alg !== $algorithm) {
            throw new RuntimeException("Token algorithm mismatch. Expected: {$algorithm}, Got: {$header->alg}");
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $expectedSignatureEncoded = $this->base64UrlEncode($expectedSignature);

        if (! hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
            throw new RuntimeException('JWT signature verification failed');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), false, 512, JSON_THROW_ON_ERROR);

        // Validate expiration (exp claim)
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new RuntimeException('JWT token has expired');
        }

        // Validate not before (nbf claim)
        if (isset($payload->nbf) && $payload->nbf > time()) {
            throw new RuntimeException('JWT token is not yet valid');
        }

        return $payload;
    }

    /**
     * Base64 URL-safe decoding.
     *
     * @param  string  $data  The data to decode
     * @return string The decoded string
     *
     * @throws RuntimeException If decoding fails
     */
    private function base64UrlDecode(string $data): string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Failed to decode JWT token');
        }

        return $decoded;
    }

    /**
     * Base64 URL-safe encoding (used for signature verification).
     *
     * @param  string  $data  The data to encode
     * @return string The encoded string
     */
    private function base64UrlEncode(string $data): string
    {
        return mb_rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
