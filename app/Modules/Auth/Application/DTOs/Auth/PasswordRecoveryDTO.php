<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs\Auth;

final class PasswordRecoveryDTO
{
    public function __construct(
        public readonly string $email
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
            email: (string) ($data['email'] ?? '')
        );
    }
}

