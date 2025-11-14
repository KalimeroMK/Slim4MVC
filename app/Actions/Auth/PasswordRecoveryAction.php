<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTO\Auth\PasswordRecoveryDTO;
use App\Events\Dispatcher;
use App\Events\PasswordResetRequested;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Models\User;
use Random\RandomException;

class PasswordRecoveryAction implements PasswordRecoveryActionInterface
{
    public function __construct(
        protected Dispatcher $dispatcher
    ) {}

    /**
     * @throws RandomException
     */
    public function execute(PasswordRecoveryDTO $dto): void
    {
        $user = User::where('email', $dto->email)->first();

        if (!$user) {
            return; // Don't reveal if user exists
        }

        $resetToken = bin2hex(random_bytes(16));
        $user->password_reset_token = $resetToken;
        $user->save();

        // Dispatch event instead of sending email directly
        $this->dispatcher->dispatch(new PasswordResetRequested($user, $resetToken));
    }
}
