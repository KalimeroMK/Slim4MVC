<?php

declare(strict_types=1);

namespace App\DTO\Role;

class UpdateRoleDTO
{
    public function __construct(
        public int $id,
        public string $name
    ) {}
}
