<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendEmailJob;
use App\Queue\Queue;

class SendWelcomeEmail
{
    public function __construct(
        private readonly Queue $queue
    ) {}

    public function handle(UserRegistered $event): void
    {
        // Queue the email job instead of sending synchronously
        $this->queue->push(new SendEmailJob(
            $event->user->email,
            'Welcome to our platform!',
            'email.welcome',
            ['user' => $event->user]
        ));
    }
}
