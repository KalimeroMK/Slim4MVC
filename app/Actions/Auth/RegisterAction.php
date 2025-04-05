<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\RegisterDTO;
use App\Interface\Auth\RegisterActionInterface;
use App\Models\User;

class RegisterAction implements RegisterActionInterface
{
    public function execute(RegisterDTO $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => password_hash($dto->password, PASSWORD_BCRYPT),
        ]);
    }
}
