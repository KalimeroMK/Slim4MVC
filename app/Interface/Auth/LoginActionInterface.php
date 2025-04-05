<?php

declare(strict_types=1);

namespace App\Interface\Auth;

use App\DTO\Auth\LoginDTO;
use RuntimeException;

interface LoginActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(LoginDTO $dto): ?array;
}
