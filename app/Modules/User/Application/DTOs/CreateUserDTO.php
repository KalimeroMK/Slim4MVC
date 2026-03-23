<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

class CreateUserDTO
{
    public function __construct(public string $name, public string $email, public string $password) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password']
        );
    }
}
