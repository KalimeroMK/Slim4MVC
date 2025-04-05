<?php

declare(strict_types=1);

// src/DTO/Auth/ResetPasswordDTO.php

namespace App\DTO\Auth;

readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $password
    ) {}
}
