<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Listeners;

use App\Modules\Core\Infrastructure\Events\PasswordResetRequested;
use App\Modules\Core\Infrastructure\Jobs\SendEmailJob;
use App\Modules\Core\Infrastructure\Queue\Queue;

class SendPasswordResetEmail
{
    public function __construct(
        private readonly Queue $queue
    ) {}

    public function handle(PasswordResetRequested $passwordResetRequested): void
    {
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:81';

        // Queue the email job instead of sending synchronously
        $this->queue->push(new SendEmailJob(
            $passwordResetRequested->user->email,
            'Password Reset Request',
            'email.password-reset',
            [
                'user' => $passwordResetRequested->user,
                'token' => $passwordResetRequested->token,
                'resetUrl' => mb_rtrim($appUrl, '/').'/reset-password/'.$passwordResetRequested->token,
            ]
        ));
    }
}
