<?php

declare(strict_types=1);

namespace App\Interface\Auth;

use App\DTO\Auth\PasswordRecoveryDTO;
use Random\RandomException;

interface PasswordRecoveryActionInterface
{
    /**
     * Initiate password recovery process
     *
     * @throws RandomException
     */
    public function execute(PasswordRecoveryDTO $dto): void;
}
