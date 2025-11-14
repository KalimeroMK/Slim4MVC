<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

class CreateUserDTO
{
    public string $name;

    public string $email;

    public string $password;

    public function __construct(string $name, string $email, string $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password']
        );
    }
}
