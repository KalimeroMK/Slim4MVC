<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs\Auth;

final class ResetPasswordDTO
{
    public function __construct(
        public readonly string $token,
        public readonly string $password
    ) {}

    /**
     * Create DTO from request data.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            token: (string) ($data['token'] ?? ''),
            password: (string) ($data['password'] ?? '')
        );
    }
}

