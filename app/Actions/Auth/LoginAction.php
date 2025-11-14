<?php

declare(strict_types=1);

// src/Actions/Auth/LoginAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\LoginDTO;
use App\Exceptions\InvalidCredentialsException;
use App\Interface\Auth\LoginActionInterface;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use RuntimeException;

class LoginAction implements LoginActionInterface
{
    public function __construct(
        protected UserRepository $repository
    ) {}

    /**
     * Execute user login.
     *
     * @return array{user: \App\Models\User, token: string}
     *
     * @throws InvalidCredentialsException
     * @throws RuntimeException
     */
    public function execute(LoginDTO $dto): array
    {
        $user = $this->repository->findByEmail($dto->email);

        if (! $user || ! password_verify($dto->password, $user->password)) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        $jwtSecret = $_ENV['JWT_SECRET'] ?? null;

        if (! $jwtSecret) {
            throw new RuntimeException('JWT_SECRET is not configured');
        }

        return [
            'user' => $user,
            'token' => JWT::encode([
                'id' => $user->id,
                'email' => $user->email,
                'exp' => time() + 60 * 60 * 24,
            ], $jwtSecret, 'HS256'),
        ];
    }
}
