<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\User\Infrastructure\Models\User;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles JWT-based authentication for API requests.
 * Reads the Bearer token from the PSR-7 request — no $_SERVER globals.
 */
final class JwtAuth
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Resolve the authenticated user from a PSR-7 request's Authorization header.
     */
    public function userFromRequest(ServerRequestInterface $request): ?User
    {
        $header = $request->getHeaderLine('Authorization');

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $this->userFromToken($token);
    }

    /**
     * Resolve the authenticated user from a raw JWT token string.
     */
    public function userFromToken(string $token): ?User
    {
        try {
            $decoded = $this->jwtService->decode($token);

            /** @var User|null $user */
            $user = User::find($decoded->id);

            return $user;
        } catch (Exception $e) {
            $this->logger?->warning('JWT authentication failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20).'...',
            ]);

            return null;
        }
    }
}
