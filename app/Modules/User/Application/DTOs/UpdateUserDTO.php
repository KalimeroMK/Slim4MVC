<?php

declare(strict_types=1);

namespace App\Modules\User\Application\DTOs;

class UpdateUserDTO
{
    public function __construct(public int $id, public ?string $name = null, public ?string $email = null) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            id: $validated['id'],
            name: $validated['name'],
            email: $validated['email']
        );
    }
}
