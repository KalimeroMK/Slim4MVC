<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\Interfaces\Auth\WebLoginActionInterface;
use App\Modules\Core\Infrastructure\Support\Auth;
use RuntimeException;

final class WebLoginAction implements WebLoginActionInterface
{
    public function __construct(
        private readonly Auth $auth
    ) {}

    /**
     * Execute web login action (session-based).
     *
     * @param LoginDTO $dto
     * @return void
     * @throws RuntimeException
     */
    public function execute(LoginDTO $dto): void
    {
        if (! $this->auth->attempt($dto->email, $dto->password)) {
            throw new RuntimeException('Invalid credentials');
        }
    }
}

