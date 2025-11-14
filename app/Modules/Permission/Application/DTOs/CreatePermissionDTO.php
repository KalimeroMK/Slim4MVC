<?php

declare(strict_types=1);

namespace App\DTO\Permission;

final readonly class CreatePermissionDTO
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
        );
    }
}
