<?php

declare(strict_types=1);

namespace App\Interface\User;

use App\DTO\User\UpdateUserDTO;
use RuntimeException;

interface UpdateUserActionInterface
{
    /**
     * Authenticate user and return token
     *
     * @throws RuntimeException On invalid credentials
     */
    public function execute(UpdateUserDTO $dto): ?array;
}
