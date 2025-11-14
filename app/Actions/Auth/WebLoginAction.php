<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTO\Auth\LoginDTO;
use App\Exceptions\InvalidCredentialsException;
use App\Interface\Auth\WebLoginActionInterface;
use App\Repositories\UserRepository;
use App\Support\Auth;

readonly class WebLoginAction implements WebLoginActionInterface
{
    public function __construct(
        private Auth $auth,
        private UserRepository $repository
    ) {}

    /**
     * Execute web login.
     *
     * @param LoginDTO $dto
     * @return void
     * @throws InvalidCredentialsException
     */
    public function execute(LoginDTO $dto): void
    {
        $user = $this->repository->findByEmail($dto->email);

        if (! $user || ! password_verify($dto->password, $user->password)) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        $this->auth->attempt($dto->email, $dto->password);
    }
}
