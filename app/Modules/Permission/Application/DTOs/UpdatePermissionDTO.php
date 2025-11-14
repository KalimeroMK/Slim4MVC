<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\DTOs;

final class UpdatePermissionDTO
{
    public function __construct(
        public int $id,
        public string $name
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            id: $validated['id'],
            name: $validated['name'],
        );
    }
}
