<?php

declare(strict_types=1);

// src/Actions/Auth/ResetPasswordAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\ResetPasswordDTO;
use App\Interface\Auth\ResetPasswordActionInterface;
use App\Models\User;

class ResetPasswordAction implements ResetPasswordActionInterface
{
    public function execute(ResetPasswordDTO $dto): void
    {
        $user = User::where('password_reset_token', $dto->token)->first();

        $user->password = password_hash($dto->password, PASSWORD_DEFAULT);
        $user->password_reset_token = null;
        $user->save();
    }
}
