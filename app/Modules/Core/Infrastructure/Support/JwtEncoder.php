<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use RuntimeException;

/**
 * JWT Token Encoder
 *
 * Encodes JWT tokens using HMAC-SHA256 algorithm.
 * Implements RFC 7519 JSON Web Token standard.
 */
class JwtEncoder
{
    /**
     * Encode a payload into a JWT token.
     *
     * @param  array<string, mixed>  $payload  The payload data to encode
     * @param  string  $secret  The secret key for signing
     * @param  string  $algorithm  The algorithm to use (default: HS256)
     * @return string The encoded JWT token
     *
     * @throws RuntimeException If secret is empty or encoding fails
     */
    public function encode(array $payload, string $secret, string $algorithm = 'HS256'): string
    {
        if ($secret === '' || $secret === '0') {
            throw new RuntimeException('JWT secret cannot be empty');
        }

        if ($algorithm !== 'HS256') {
            throw new RuntimeException("Unsupported algorithm: {$algorithm}. Only HS256 is supported.");
        }

        // Add standard claims if not present
        if (! isset($payload['iat'])) {
            $payload['iat'] = time(); // Issued at
        }

        // Encode header
        $header = [
            'typ' => 'JWT',
            'alg' => $algorithm,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        // Create signature
        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Base64 URL-safe encoding.
     *
     * @param  string  $data  The data to encode
     * @return string The encoded string
     */
    private function base64UrlEncode(string $data): string
    {
        return mb_rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
