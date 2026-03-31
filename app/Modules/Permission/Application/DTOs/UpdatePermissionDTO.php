<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\DTOs;

final class UpdatePermissionDTO
{
    /**
     * @param  list<int>|list<string>  $roles
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $roles = [],
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(int $id, array $validated): self
    {
        return new self(
            id: $id,
            name: $validated['name'] ?? '',
            roles: $validated['roles'] ?? [],
        );
    }
}
