<?php

declare(strict_types=1);

// src/DTO/Auth/PasswordRecoveryDTO.php

namespace App\DTO\Auth;

readonly class PasswordRecoveryDTO
{
    public function __construct(
        public string $email
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            email: $validated['email'],
        );
    }
}
