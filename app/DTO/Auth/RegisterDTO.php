<?php

declare(strict_types=1);

// src/DTO/Auth/RegisterDTO.php

namespace App\DTO\Auth;

readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password']
        );
    }
}
