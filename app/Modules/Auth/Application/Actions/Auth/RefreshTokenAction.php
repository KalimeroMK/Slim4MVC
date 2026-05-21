<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Core\Infrastructure\Support\AdvancedJwtServiceInterface as AdvancedJwtService;
use App\Modules\Core\Infrastructure\Support\Token\TokenPair;
use RuntimeException;

final readonly class RefreshTokenAction
{
    public function __construct(
        private AdvancedJwtService $jwtService
    ) {}

    /**
     * Rotate the refresh token and issue a new token pair.
     *
     * @throws RuntimeException on invalid, revoked, or reused token
     */
    public function execute(string $refreshToken): TokenPair
    {
        return $this->jwtService->rotateRefreshToken($refreshToken);
    }
}
