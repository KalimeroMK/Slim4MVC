<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTO\User\CreateUserDTO;
use App\Interface\User\CreateUserActionInterface;
use App\Models\User;

final class CreateUserAction implements CreateUserActionInterface
{
    /**
     * Execute user creation.
     */
    public function execute(CreateUserDTO $dto): array
    {
        // Hash the password and create user
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => password_hash($dto->password, PASSWORD_BCRYPT),
        ]);

        return $user->toArray();
    }
}
