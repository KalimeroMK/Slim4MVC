<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\Interfaces\Auth\LoginActionInterface;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Support\JwtService;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use RuntimeException;

final class LoginAction implements LoginActionInterface
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly JwtService $jwtService
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

        $payload = [
            'id' => $user->id,
            'email' => $user->email,
        ];

        // Token expires in 24 hours
        $token = $this->jwtService->encode($payload, 60 * 60 * 24);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
