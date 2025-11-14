<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Jobs\SendEmailJob;
use App\Queue\Queue;

class SendPasswordResetEmail
{
    public function __construct(
        private readonly Queue $queue
    ) {}

    public function handle(PasswordResetRequested $event): void
    {
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:81';
        
        // Queue the email job instead of sending synchronously
        $this->queue->push(new SendEmailJob(
            $event->user->email,
            'Password Reset Request',
            'email.password-reset',
            [
                'user' => $event->user,
                'token' => $event->token,
                'resetUrl' => rtrim($appUrl, '/').'/reset-password/'.$event->token,
            ]
        ));
    }
}

