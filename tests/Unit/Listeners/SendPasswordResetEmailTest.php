<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Modules\Core\Infrastructure\Events\PasswordResetRequested;
use App\Modules\Core\Infrastructure\Jobs\SendEmailJob;
use App\Modules\Core\Infrastructure\Listeners\SendPasswordResetEmail;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\User\Infrastructure\Models\User;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Tests\TestCase;

final class SendPasswordResetEmailTest extends TestCase
{
    private SendPasswordResetEmail $sendPasswordResetEmail;

    private MockObject $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = $this->createMock(Queue::class);
        $this->sendPasswordResetEmail = new SendPasswordResetEmail($this->queue);
    }

    public function test_handle_queues_password_reset_email_job(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $token = 'reset-token-123';
        $passwordResetRequested = new PasswordResetRequested($user, $token);

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(function (SendEmailJob $sendEmailJob) use ($user): true {
                // Verify job properties using reflection
                $reflectionClass = new ReflectionClass($sendEmailJob);
                $reflectionProperty = $reflectionClass->getProperty('to');

                $subjectProperty = $reflectionClass->getProperty('subject');

                $templateProperty = $reflectionClass->getProperty('template');
                $this->assertSame($user->email, $reflectionProperty->getValue($sendEmailJob));
                $this->assertSame('Password Reset Request', $subjectProperty->getValue($sendEmailJob));
                $this->assertSame('email.password-reset', $templateProperty->getValue($sendEmailJob));

                return true;
            }));

        $this->sendPasswordResetEmail->handle($passwordResetRequested);
    }
}
