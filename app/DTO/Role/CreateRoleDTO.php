<?php

declare(strict_types=1);

namespace App\DTO\Role;

class CreateRoleDTO
{
    public function __construct(
        public string $name,
    ) {}
}
