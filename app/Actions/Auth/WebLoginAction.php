<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTO\Auth\LoginDTO;
use App\Interface\Auth\WebLoginActionInterface;
use App\Models\User;
use App\Support\Auth;
use RuntimeException;

readonly class WebLoginAction implements WebLoginActionInterface
{
    public function __construct(
        private Auth $auth
    ) {}

    public function execute(LoginDTO $dto): void
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! password_verify($dto->password, $user->password)) {
            throw new RuntimeException('Invalid credentials');
        }

        $this->auth->attempt($dto->email, $dto->password);
    }
}
