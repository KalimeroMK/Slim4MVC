<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\PasswordResetRequested;
use App\Jobs\SendEmailJob;
use App\Listeners\SendPasswordResetEmail;
use App\Models\User;
use App\Queue\Queue;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SendPasswordResetEmailTest extends TestCase
{
    private SendPasswordResetEmail $listener;
    private MockObject $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = $this->createMock(Queue::class);
        $this->listener = new SendPasswordResetEmail($this->queue);
    }

    public function test_handle_queues_password_reset_email_job(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $token = 'reset-token-123';
        $event = new PasswordResetRequested($user, $token);

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(function (SendEmailJob $job) use ($user, $token) {
                // Verify job properties using reflection
                $reflection = new \ReflectionClass($job);
                $toProperty = $reflection->getProperty('to');
                $toProperty->setAccessible(true);
                $subjectProperty = $reflection->getProperty('subject');
                $subjectProperty->setAccessible(true);
                $templateProperty = $reflection->getProperty('template');
                $templateProperty->setAccessible(true);

                return $toProperty->getValue($job) === $user->email
                    && $subjectProperty->getValue($job) === 'Password Reset Request'
                    && $templateProperty->getValue($job) === 'email.password-reset';
            }));

        $this->listener->handle($event);
    }
}

