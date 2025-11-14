<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\Interfaces\Auth\LoginActionInterface;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Firebase\JWT\JWT;
use RuntimeException;

final class LoginAction implements LoginActionInterface
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute login action.
     *
     * @return array<string, mixed>
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

        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'exp' => time() + (60 * 60 * 24), // 24 hours
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
