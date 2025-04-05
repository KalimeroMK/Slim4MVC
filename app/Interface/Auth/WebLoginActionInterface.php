<?php

declare(strict_types=1);

namespace App\Interface\Auth;

use App\DTO\Auth\LoginDTO;
use RuntimeException;

interface WebLoginActionInterface
{
    /**
     * Authenticate user and establish session
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(LoginDTO $dto): void;
}
