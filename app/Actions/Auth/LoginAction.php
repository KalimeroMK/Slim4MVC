<?php

declare(strict_types=1);

// src/Actions/Auth/LoginAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\LoginDTO;
use App\Interface\Auth\LoginActionInterface;
use App\Models\User;
use Firebase\JWT\JWT;
use RuntimeException;

class LoginAction implements LoginActionInterface
{
    public function execute(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! password_verify($dto->password, $user->password)) {
            throw new RuntimeException('Invalid credentials');
        }

        return [
            'user' => $user,
            'token' => JWT::encode([
                'id' => $user->id,
                'email' => $user->email,
                'exp' => time() + 60 * 60 * 24,
            ], $_ENV['JWT_SECRET'], 'HS256'),
        ];
    }
}
