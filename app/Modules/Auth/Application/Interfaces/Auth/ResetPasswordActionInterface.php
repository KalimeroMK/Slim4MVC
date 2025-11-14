<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Interfaces\Auth;

use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;

interface ResetPasswordActionInterface
{
    /**
     * Execute password reset action.
     *
     * @param ResetPasswordDTO $dto
     * @return void
     */
    public function execute(ResetPasswordDTO $dto): void;
}

