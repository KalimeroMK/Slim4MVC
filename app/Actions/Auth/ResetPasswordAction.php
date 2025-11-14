<?php

declare(strict_types=1);

// src/Actions/Auth/ResetPasswordAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\ResetPasswordDTO;
use App\Interface\Auth\ResetPasswordActionInterface;
use App\Models\User;
use RuntimeException;

class ResetPasswordAction implements ResetPasswordActionInterface
{
    public function execute(ResetPasswordDTO $dto): void
    {
        $user = User::where('password_reset_token', $dto->token)->first();

        if (! $user) {
            throw new RuntimeException('Invalid or expired reset token');
        }

        $user->password = password_hash($dto->password, PASSWORD_BCRYPT);
        $user->password_reset_token = null;
        $user->save();
    }
}
