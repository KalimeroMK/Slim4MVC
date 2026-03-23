<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Interfaces;

use App\Modules\User\Application\DTOs\UpdateUserDTO;
use App\Modules\User\Infrastructure\Models\User;
use RuntimeException;

interface UpdateUserActionInterface
{
    /**
     * Update an existing user
     *
     * @throws RuntimeException On update failure
     */
    public function execute(UpdateUserDTO $updateUserDTO): User;
}
