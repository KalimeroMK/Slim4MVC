<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Interfaces;

use App\Modules\User\Application\DTOs\UpdateUserDTO;
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
