<?php

namespace App\DTO\Role;

class CreateRoleDTO
{
    public function __construct(
        public string $name,
    ) {}
}