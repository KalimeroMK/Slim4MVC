<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\Interfaces\Auth\LoginActionInterface;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Support\AdvancedJwtServiceInterface as AdvancedJwtService;
use App\Modules\Core\Infrastructure\Support\Token\TokenPair;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class LoginAction implements LoginActionInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private AdvancedJwtService $jwtService
    ) {}

    /**
     * Execute login action — returns user + a full token pair (access + refresh).
     *
     * @return array{user: User, token_pair: TokenPair}
     *
     * @throws InvalidCredentialsException
     */
    public function execute(LoginDTO $loginDTO): array
    {
        $user = $this->userRepository->findByEmail($loginDTO->email);

        if (! $user instanceof User || ! password_verify($loginDTO->password, (string) $user->password)) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        $tokenPair = $this->jwtService->generateRefreshToken($user->id);

        return [
            'user'       => $user,
            'token_pair' => $tokenPair,
        ];
    }
}
