<?php

declare(strict_types=1);

namespace App\Modules\Permission\Application\DTOs;

final readonly class CreatePermissionDTO
{
    /**
     * @param  list<int>|list<string>  $roles
     */
    public function __construct(
        public string $name,
        public array $roles = [],
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            roles: $validated['roles'] ?? [],
        );
    }
}
