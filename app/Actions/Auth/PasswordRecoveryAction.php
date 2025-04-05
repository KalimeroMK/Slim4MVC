<?php

declare(strict_types=1);

// src/Actions/Auth/PasswordRecoveryAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\PasswordRecoveryDTO;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Models\User;
use App\Trait\SendPassword;
use Random\RandomException;

class PasswordRecoveryAction implements PasswordRecoveryActionInterface
{
    use SendPassword;

    /**
     * @throws RandomException
     */
    public function execute(PasswordRecoveryDTO $dto): void
    {
        $user = User::where('email', $dto->email)->first();
        $resetToken = bin2hex(random_bytes(16));

        $user->password_reset_token = $resetToken;
        $user->save();

        $this->sendPasswordResetEmail($user->email, $resetToken);
    }
}
