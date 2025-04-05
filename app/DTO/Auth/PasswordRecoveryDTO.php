<?php

declare(strict_types=1);

// src/DTO/Auth/PasswordRecoveryDTO.php

namespace App\DTO\Auth;

readonly class PasswordRecoveryDTO
{
    public function __construct(
        public string $email
    ) {}
}
