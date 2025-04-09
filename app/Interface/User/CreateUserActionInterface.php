<?php

declare(strict_types=1);

namespace App\Interface\User;

use App\DTO\User\CreateUserDTO;
use RuntimeException;

interface CreateUserActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(CreateUserDTO $dto): ?array;
}
