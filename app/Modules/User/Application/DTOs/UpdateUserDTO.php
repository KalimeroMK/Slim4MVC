<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

class UpdateUserDTO
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(int $id, array $validated): self
    {
        return new self(
            id: $id,
            name: $validated['name'] ?? null,
            email: $validated['email'] ?? null,
            password: $validated['password'] ?? null,
        );
    }
}
