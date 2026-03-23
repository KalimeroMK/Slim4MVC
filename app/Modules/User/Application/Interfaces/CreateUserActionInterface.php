<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Interfaces;

use App\Modules\User\Application\DTOs\CreateUserDTO;
use App\Modules\User\Infrastructure\Models\User;
use RuntimeException;

interface CreateUserActionInterface
{
    /**
     * Create a new user
     *
     * @throws RuntimeException On creation failure
     */
    public function execute(CreateUserDTO $createUserDTO): User;
}
