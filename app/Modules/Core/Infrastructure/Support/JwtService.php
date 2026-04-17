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
    private readonly JwtEncoder $jwtEncoder;

    private readonly JwtDecoder $jwtDecoder;

    private readonly string $secret;

    /**
     * @param  string|null  $secret  The JWT secret key (defaults to JWT_SECRET env var)
     * @param  string  $algorithm  The algorithm to use (default: HS256)
     *
     * @throws RuntimeException If secret is not configured
     */
    public function __construct(string $secret, private readonly string $algorithm = 'HS256')
    {
        $this->jwtEncoder = new JwtEncoder();
        $this->jwtDecoder = new JwtDecoder();
        $this->secret = $secret;

        if ($this->secret === '' || $this->secret === '0') {
            throw new RuntimeException('JWT secret cannot be empty');
        }

        if (mb_strlen($this->secret) < 32) {
            throw new RuntimeException('JWT secret must be at least 32 characters long for security');
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

        return $this->jwtEncoder->encode($payload, $this->secret, $this->algorithm);
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
        return $this->jwtDecoder->decode($token, $this->secret, $this->algorithm);
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
