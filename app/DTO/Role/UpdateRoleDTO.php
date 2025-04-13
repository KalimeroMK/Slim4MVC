<?php

declare(strict_types=1);

namespace App\DTO\Role;

final class UpdateRoleDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public array $permissions
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            id: $validated['id'],
            name: $validated['name'],
            permissions: $validated['permission']
        );
    }
}
