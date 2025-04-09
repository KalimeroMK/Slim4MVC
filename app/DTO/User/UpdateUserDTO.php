<?php

declare(strict_types=1);

namespace App\DTO\User;

class UpdateUserDTO
{
    public int $id;

    public ?string $name;

    public ?string $email;

    public function __construct(int $id, ?string $name = null, ?string $email = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }
}
