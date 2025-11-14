<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Mail;

use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\View\Blade;
use App\Modules\User\Infrastructure\Models\User;

/**
 * Password reset email mailable.
 */
class PasswordResetEmail extends Mailable
{
    protected User $user;

    protected string $token;

    public function __construct(
        Mailer $mailer,
        Blade $blade,
        User $user,
        string $token
    ) {
        parent::__construct($mailer, $blade);
        $this->user = $user;
        $this->token = $token;
        $this->to($user->email);
    }

    protected function template(): string
    {
        return 'email.reset-password';
    }

    protected function getSubject(): string
    {
        return 'Reset Your Password';
    }

    protected function buildData(): array
    {
        $resetUrl = ($_ENV['APP_URL'] ?? 'http://localhost:81').'/reset-password?token='.$this->token;

        return [
            'user' => $this->user,
            'token' => $this->token,
            'resetUrl' => $resetUrl,
            'resetLink' => $resetUrl, // Alias for template compatibility
        ];
    }
}
