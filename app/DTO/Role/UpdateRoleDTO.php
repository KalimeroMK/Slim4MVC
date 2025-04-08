<?php

namespace App\DTO\Role;

class UpdateRoleDTO
{
    public function __construct(
        public int $id,
        public string $name
    ) {}
}