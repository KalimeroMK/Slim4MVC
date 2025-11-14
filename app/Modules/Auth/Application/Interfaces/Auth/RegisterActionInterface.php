<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Interfaces\Auth;

use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\User\Infrastructure\Models\User;

interface RegisterActionInterface
{
    /**
     * Execute registration action.
     *
     * @param RegisterDTO $dto
     * @return User
     */
    public function execute(RegisterDTO $dto): User;
}

