<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Core\Infrastructure\Support\AdvancedJwtServiceInterface as AdvancedJwtService;

final readonly class LogoutAction
{
    public function __construct(
        private AdvancedJwtService $jwtService
    ) {}

    /**
     * Revoke the given refresh token JTI (if provided) on logout.
     * Access tokens are short-lived (15 min) so only refresh tokens are tracked.
     */
    public function execute(?string $jti = null): void
    {
        if ($jti !== null && $jti !== '') {
            $this->jwtService->revokeRefreshToken($jti);
        }
    }
}
