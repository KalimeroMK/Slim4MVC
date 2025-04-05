<?php

declare(strict_types=1);

namespace App\Interface\Auth;

use App\DTO\Auth\ResetPasswordDTO;

interface ResetPasswordActionInterface
{
    /**
     * Reset user password using valid token
     */
    public function execute(ResetPasswordDTO $dto): void;
}
