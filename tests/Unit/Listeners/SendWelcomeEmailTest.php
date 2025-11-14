<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendEmailJob;
use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use App\Queue\Queue;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Tests\TestCase;

class SendWelcomeEmailTest extends TestCase
{
    private SendWelcomeEmail $listener;

    private MockObject $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = $this->createMock(Queue::class);
        $this->listener = new SendWelcomeEmail($this->queue);
    }

    public function test_handle_queues_email_job(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $event = new UserRegistered($user);

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(function (SendEmailJob $job) use ($user) {
                // Verify job properties using reflection
                $reflection = new ReflectionClass($job);
                $toProperty = $reflection->getProperty('to');
                $toProperty->setAccessible(true);
                $subjectProperty = $reflection->getProperty('subject');
                $subjectProperty->setAccessible(true);
                $templateProperty = $reflection->getProperty('template');
                $templateProperty->setAccessible(true);

                return $toProperty->getValue($job) === $user->email
                    && $subjectProperty->getValue($job) === 'Welcome to our platform!'
                    && $templateProperty->getValue($job) === 'email.welcome';
            }));

        $this->listener->handle($event);
    }
}
