<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\Core\Infrastructure\Support\Token\TokenPair;
use App\Modules\Core\Infrastructure\Validation\ConfigurationException;
use App\Modules\Core\Infrastructure\Validation\EnvironmentValidator;
use Predis\Client;
use RuntimeException;
use stdClass;

/**
 * Advanced JWT Service with enhanced security features.
 *
 * Combines features from both approaches:
 * - Token pairs (access + refresh)
 * - Refresh token rotation
 * - Fingerprint-based security
 * - Redis-backed token whitelist
 * - Comprehensive claims support
 */
final readonly class AdvancedJwtService
{
    private const int ACCESS_TOKEN_TTL = 900;
          // 15 minutes
    private const int REFRESH_TOKEN_TTL = 2592000;  // 30 days

    private JwtEncoder $jwtEncoder;

    private JwtDecoder $jwtDecoder;

    public function __construct(
        private string $secret,
        private string $algorithm = 'HS256',
        private int $defaultTtl = self::ACCESS_TOKEN_TTL,
        private ?string $issuer = null,
        private ?string $audience = null,
        private ?Client $client = null
    ) {
        EnvironmentValidator::assertJwtSecret($secret);

        if (!in_array($algorithm, ['HS256', 'HS384', 'HS512'], true)) {
            throw new RuntimeException('Unsupported algorithm: ' . $algorithm);
        }

        $this->jwtEncoder = new JwtEncoder();
        $this->jwtDecoder = new JwtDecoder();
    }

    /**
     * Generate an access token with security claims.
     *
     * @param int|string $userId The user identifier
     * @param array<string, mixed> $claims Additional claims
     * @param int|null $ttl Custom TTL (null uses default)
     */
    public function generateAccessToken(int|string $userId, array $claims = [], ?int $ttl = null): string
    {
        $payload = array_merge($claims, [
            'sub' => (string) $userId,
            'type' => 'access',
            'iat' => time(),
            'jti' => $this->generateUniqueId(16),
            'exp' => time() + ($ttl ?? $this->defaultTtl),
            'nbf' => time(),
        ]);

        // Add issuer and audience if configured
        if ($this->issuer !== null) {
            $payload['iss'] = $this->issuer;
        }

        if ($this->audience !== null) {
            $payload['aud'] = $this->audience;
        }

        return $this->encode($payload);
    }

    /**
     * Generate a refresh token with rotation support.
     *
     * @param int|string $userId The user identifier
     */
    public function generateRefreshToken(int|string $userId): TokenPair
    {
        $jti = $this->generateUniqueId(32);
        $fingerprint = $this->generateFingerprint();

        $payload = [
            'sub' => (string) $userId,
            'type' => 'refresh',
            'jti' => $jti,
            'fp' => $fingerprint,
            'iat' => time(),
            'exp' => time() + self::REFRESH_TOKEN_TTL,
        ];

        $refreshToken = $this->encode($payload);

        // Store in Redis for rotation tracking
        if ($this->client !== null) {
            $this->client->setex(
                $this->getRefreshTokenKey($jti),
                self::REFRESH_TOKEN_TTL,
                json_encode([
                    'user_id' => $userId,
                    'fingerprint' => $fingerprint,
                    'created_at' => time(),
                ])
            );
        }

        return new TokenPair(
            accessToken: $this->generateAccessToken($userId),
            refreshToken: $refreshToken,
            expiresIn: self::REFRESH_TOKEN_TTL
        );
    }

    /**
     * Rotate refresh token (invalidate old, issue new).
     *
     * Implements Refresh Token Rotation pattern for enhanced security.
     *
     * @throws RuntimeException If rotation fails or security violation detected
     */
    public function rotateRefreshToken(string $refreshToken): TokenPair
    {
        $payload = $this->decode($refreshToken);

        // Validate token type first
        if (($payload->type ?? '') !== 'refresh') {
            throw new RuntimeException('Invalid token type: expected refresh token');
        }

        // Check if Redis is available for token rotation
        if ($this->client === null) {
            throw new RuntimeException('Token rotation requires Redis. Please configure Redis or generate a new token pair.');
        }

        // Check fingerprint for potential token theft
        if (!$this->validateFingerprint($payload->fp ?? '')) {
            // Security violation - revoke all tokens for this user
            $this->revokeAllUserTokens($payload->sub);
            throw new RuntimeException('Token reuse detected. All tokens revoked for security.');
        }

        // Verify token is in whitelist (not revoked)
        $stored = $this->client->get($this->getRefreshTokenKey($payload->jti));

        if ($stored === null) {
            // Token was already used or revoked
            $this->revokeAllUserTokens($payload->sub);
            throw new RuntimeException('Refresh token has been revoked or already used');
        }

        // Delete old token (rotation)
        $this->client->del($this->getRefreshTokenKey($payload->jti));

        // Generate new token pair
        return $this->generateRefreshToken($payload->sub);
    }

    /**
     * Decode and validate a JWT token.
     *
     * @param string $token The JWT token
     * @param bool $validateIssuer Whether to validate the issuer
     * @param bool $validateAudience Whether to validate the audience
     *
     * @return stdClass The decoded payload
     *
     * @throws RuntimeException If token is invalid
     */
    public function decode(string $token, bool $validateIssuer = false, bool $validateAudience = false): stdClass
    {
        $payload = $this->jwtDecoder->decode($token, $this->secret, $this->algorithm);

        // Validate issuer if requested
        if ($validateIssuer && $this->issuer !== null && ($payload->iss ?? '') !== $this->issuer) {
            throw new RuntimeException('Invalid token issuer');
        }

        // Validate audience if requested
        if ($validateAudience && $this->audience !== null && ($payload->aud ?? '') !== $this->audience) {
            throw new RuntimeException('Invalid token audience');
        }

        return $payload;
    }

    /**
     * Verify token without full decode (for quick checks).
     */
    public function verify(string $token): bool
    {
        try {
            $this->decode($token);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Get token information without signature verification.
     *
     * @return array<string, mixed>
     */
    public function getTokenInfo(string $token): array
    {
        try {
            // Decode without verification for inspection
            $payload = $this->jwtDecoder->decode($token, $this->secret, $this->algorithm);

            return [
                'valid' => true,
                'algorithm' => $this->algorithm,
                'type' => $payload->type ?? 'unknown',
                'issuer' => $payload->iss ?? null,
                'audience' => $payload->aud ?? null,
                'subject' => $payload->sub ?? null,
                'issued_at' => isset($payload->iat) ? date('Y-m-d H:i:s', (int) $payload->iat) : null,
                'expires_at' => isset($payload->exp) ? date('Y-m-d H:i:s', (int) $payload->exp) : null,
                'is_expired' => isset($payload->exp) && $payload->exp < time(),
                'jwt_id' => $payload->jti ?? null,
            ];
        } catch (RuntimeException | \JsonException $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Revoke a specific refresh token.
     */
    public function revokeRefreshToken(string $jti): void
    {
        if ($this->client !== null) {
            $this->client->del($this->getRefreshTokenKey($jti));
        }
    }

    /**
     * Revoke all refresh tokens for a user.
     */
    public function revokeAllUserTokens(int|string $userId): void
    {
        if ($this->client === null) {
            return;
        }

        // This is a simplified implementation
        // In production, you might want to use a set or scan pattern
        $pattern = "refresh_token:*";
        $keys = $this->client->keys($pattern);

        foreach ($keys as $key) {
            $data = $this->client->get($key);
            if ($data !== null) {
                $tokenData = json_decode($data, true);
                if (isset($tokenData['user_id']) && $tokenData['user_id'] == $userId) {
                    $this->client->del($key);
                }
            }
        }
    }

    /**
     * Encode a payload into a JWT token.
     *
     * @param array<string, mixed> $payload
     */
    private function encode(array $payload): string
    {
        return $this->jwtEncoder->encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Generate a unique identifier.
     */
    private function generateUniqueId(int $length = 16): string
    {
        $bytes = (int) ceil($length / 2);
        if ($bytes < 1) {
            $bytes = 1;
        }

        return bin2hex(random_bytes($bytes));
    }

    /**
     * Generate a browser/device fingerprint.
     */
    private function generateFingerprint(): string
    {
        // Combine multiple factors for fingerprint
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_ACCEPT'] ?? 'unknown',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown',
            time(), // Add timestamp to make it unique per session
        ];

        return hash('sha256', implode('|', $data));
    }

    /**
     * Validate a fingerprint against current request.
     */
    private function validateFingerprint(string $fingerprint): bool
    {
        // In a real implementation, you might want to allow some tolerance
        // For example, IP might change in mobile networks
        $current = $this->generateFingerprint();

        // Simple exact match for now
        // Consider implementing fuzzy matching for production
        return hash_equals($fingerprint, $current);
    }

    /**
     * Get Redis key for a refresh token.
     */
    private function getRefreshTokenKey(string $jti): string
    {
        return 'refresh_token:' . $jti;
    }
}
