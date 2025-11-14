<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Interfaces;

use App\Modules\User\Application\DTOs\CreateUserDTO;
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
