<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use RuntimeException;
use stdClass;

/**
 * JWT Service
 *
 * High-level service for encoding and decoding JWT tokens.
 * Combines JwtEncoder and JwtDecoder with configuration management.
 */
class JwtService
{
    private JwtEncoder $encoder;

    private JwtDecoder $decoder;

    private string $secret;

    private string $algorithm;

    /**
     * @param  string|null  $secret  The JWT secret key (defaults to JWT_SECRET env var)
     * @param  string  $algorithm  The algorithm to use (default: HS256)
     *
     * @throws RuntimeException If secret is not configured
     */
    public function __construct(?string $secret = null, string $algorithm = 'HS256')
    {
        $this->encoder = new JwtEncoder();
        $this->decoder = new JwtDecoder();
        $this->algorithm = $algorithm;
        $this->secret = $secret ?? $_ENV['JWT_SECRET'] ?? '';

        if ($this->secret === '' || $this->secret === '0') {
            throw new RuntimeException('JWT_SECRET is not configured');
        }
    }

    /**
     * Encode a payload into a JWT token.
     *
     * @param  array<string, mixed>  $payload  The payload data to encode
     * @param  int|null  $expirationTime  Expiration time in seconds from now (optional)
     * @return string The encoded JWT token
     *
     * @throws RuntimeException If encoding fails
     */
    public function encode(array $payload, ?int $expirationTime = null): string
    {
        if ($expirationTime !== null && ! isset($payload['exp'])) {
            $payload['exp'] = time() + $expirationTime;
        }

        return $this->encoder->encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Decode and validate a JWT token.
     *
     * @param  string  $token  The JWT token to decode
     * @return stdClass The decoded payload
     *
     * @throws RuntimeException If token is invalid, expired, or verification fails
     */
    public function decode(string $token): stdClass
    {
        return $this->decoder->decode($token, $this->secret, $this->algorithm);
    }

    /**
     * Get the JWT secret key.
     *
     * @return string The secret key
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Get the algorithm being used.
     *
     * @return string The algorithm
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}
