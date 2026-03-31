<?php

declare(strict_types=1);

namespace App\Modules\Role\Application\DTOs;

final class UpdateRoleDTO
{
    /**
     * @param  list<int>|list<string>  $permissions
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $permissions = [],
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(int $id, array $validated): self
    {
        return new self(
            id: $id,
            name: $validated['name'] ?? '',
            permissions: $validated['permissions'] ?? []
        );
    }
}
