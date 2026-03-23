<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Modules\Core\Infrastructure\Events\UserRegistered;
use App\Modules\Core\Infrastructure\Jobs\SendEmailJob;
use App\Modules\Core\Infrastructure\Listeners\SendWelcomeEmail;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\User\Infrastructure\Models\User;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Tests\TestCase;

final class SendWelcomeEmailTest extends TestCase
{
    private SendWelcomeEmail $sendWelcomeEmail;

    private MockObject $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = $this->createMock(Queue::class);
        $this->sendWelcomeEmail = new SendWelcomeEmail($this->queue);
    }

    public function test_handle_queues_email_job(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $userRegistered = new UserRegistered($user);

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(function (SendEmailJob $sendEmailJob) use ($user): true {
                // Verify job properties using reflection
                $reflectionClass = new ReflectionClass($sendEmailJob);
                $reflectionProperty = $reflectionClass->getProperty('to');

                $subjectProperty = $reflectionClass->getProperty('subject');

                $templateProperty = $reflectionClass->getProperty('template');
                $this->assertSame($user->email, $reflectionProperty->getValue($sendEmailJob));
                $this->assertSame('Welcome to our platform!', $subjectProperty->getValue($sendEmailJob));
                $this->assertSame('email.welcome', $templateProperty->getValue($sendEmailJob));

                return true;
            }));

        $this->sendWelcomeEmail->handle($userRegistered);
    }
}
