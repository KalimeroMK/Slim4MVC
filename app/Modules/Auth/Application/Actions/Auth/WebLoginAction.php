<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\Interfaces\Auth\WebLoginActionInterface;
use App\Modules\Core\Infrastructure\Support\Auth;
use RuntimeException;

final readonly class WebLoginAction implements WebLoginActionInterface
{
    public function __construct(
        private Auth $auth
    ) {}

    /**
     * Execute web login action (session-based).
     *
     * @throws RuntimeException
     */
    public function execute(LoginDTO $loginDTO): void
    {
        if (! $this->auth->attempt($loginDTO->email, $loginDTO->password)) {
            throw new RuntimeException('Invalid credentials');
        }
    }
}
