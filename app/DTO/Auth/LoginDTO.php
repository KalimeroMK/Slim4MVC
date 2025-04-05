<?php

declare(strict_types=1);

// src/DTO/Auth/LoginDTO.php

namespace App\DTO\Auth;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password
    ) {}
}
