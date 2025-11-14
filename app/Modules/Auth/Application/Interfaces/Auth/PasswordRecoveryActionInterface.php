<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Interfaces\Auth;

use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;

interface PasswordRecoveryActionInterface
{
    /**
     * Execute password recovery action.
     */
    public function execute(PasswordRecoveryDTO $dto): void;
}
