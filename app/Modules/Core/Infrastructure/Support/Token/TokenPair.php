<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support\Token;

/**
 * Value object representing a pair of JWT tokens.
 */
final readonly class TokenPair
{
    public function __construct(
        private string $accessToken,
        private string $refreshToken,
        private ?int $expiresIn = null
    ) {}

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
        ];
    }
}
