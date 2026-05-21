<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\Core\Infrastructure\Support\Token\TokenPair;
use RuntimeException;
use stdClass;

interface AdvancedJwtServiceInterface
{
    public function generateAccessToken(int|string $userId, array $claims = [], ?int $ttl = null): string;

    public function generateRefreshToken(int|string $userId): TokenPair;

    /**
     * @throws RuntimeException
     */
    public function rotateRefreshToken(string $refreshToken): TokenPair;

    public function decode(string $token, bool $validateIssuer = false, bool $validateAudience = false): stdClass;

    public function verify(string $token): bool;

    public function revokeRefreshToken(string $jti, int|string|null $userId = null): void;

    public function revokeAllUserTokens(int|string $userId): void;
}
