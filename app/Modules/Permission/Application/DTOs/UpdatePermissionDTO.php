<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\DTOs;

final class UpdatePermissionDTO
{
    public function __construct(
        public int $id,
        public string $name
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(int $id, array $validated): self
    {
        return new self(
            id: $id,
            name: $validated['name'] ?? '',
        );
    }
}
