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
 *
 * An `exp` claim is required: a token without one is valid forever.
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
     * @throws RuntimeException If the token is invalid, expired, missing `exp`, or fails verification
     */
    public function decode(string $token, string $secret, string $algorithm = 'HS256'): stdClass
    {
        if ($secret === '' || $secret === '0') {
            throw new RuntimeException('JWT secret cannot be empty');
        }

        if ($algorithm !== 'HS256') {
            throw new RuntimeException(sprintf('Unsupported algorithm: %s. Only HS256 is supported.', $algorithm));
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWT token format');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Decode header
        $header = json_decode($this->base64UrlDecode($headerEncoded), false, 512, JSON_THROW_ON_ERROR);

        if (! isset($header->alg) || $header->alg !== $algorithm) {
            throw new RuntimeException(sprintf('Token algorithm mismatch. Expected: %s, Got: %s', $algorithm, $header->alg));
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', sprintf('%s.%s', $headerEncoded, $payloadEncoded), $secret, true);
        $expectedSignatureEncoded = $this->base64UrlEncode($expectedSignature);

        if (! hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
            throw new RuntimeException('JWT signature verification failed');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), false, 512, JSON_THROW_ON_ERROR);

        if (! $payload instanceof stdClass) {
            throw new RuntimeException('JWT payload must be a JSON object');
        }

        // Validate expiration (exp claim). A token without one would never expire,
        // so its absence is rejected rather than treated as "no expiry".
        if (! isset($payload->exp)) {
            throw new RuntimeException('JWT token is missing the exp claim');
        }

        if (! is_numeric($payload->exp)) {
            throw new RuntimeException('JWT exp claim must be a timestamp');
        }

        if ((int) $payload->exp < time()) {
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
