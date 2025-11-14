<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Interfaces\Auth;

use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;

interface PasswordRecoveryActionInterface
{
    /**
     * Execute password recovery action.
     *
     * @param PasswordRecoveryDTO $dto
     * @return void
     */
    public function execute(PasswordRecoveryDTO $dto): void;
}

