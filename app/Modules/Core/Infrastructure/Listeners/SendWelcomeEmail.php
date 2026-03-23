<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Listeners;

use App\Modules\Core\Infrastructure\Events\UserRegistered;
use App\Modules\Core\Infrastructure\Jobs\SendEmailJob;
use App\Modules\Core\Infrastructure\Queue\Queue;

class SendWelcomeEmail
{
    public function __construct(
        private readonly Queue $queue
    ) {}

    public function handle(UserRegistered $userRegistered): void
    {
        // Queue the email job instead of sending synchronously
        $this->queue->push(new SendEmailJob(
            $userRegistered->user->email,
            'Welcome to our platform!',
            'email.welcome',
            ['user' => $userRegistered->user]
        ));
    }
}
