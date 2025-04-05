<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTO\Auth\PasswordRecoveryDTO;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Models\User;
use App\Support\Mailer;
use Random\RandomException;

class PasswordRecoveryAction implements PasswordRecoveryActionInterface
{
    public function __construct(
        protected Mailer $mailService
    ) {}

    /**
     * @throws RandomException
     */
    public function execute(PasswordRecoveryDTO $dto): void
    {
        $user = User::where('email', $dto->email)->first();

        $resetToken = bin2hex(random_bytes(16));
        $user->password_reset_token = $resetToken;
        $user->save();

        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:81';
        $resetLink = rtrim($appUrl, '/') . '/reset-password/' . $resetToken;

        $this->mailService->send(
            $user->email,
            'Reset Your Password',
            'email.reset-password', // Blade template path
            ['resetLink' => $resetLink]
        );
    }
}
