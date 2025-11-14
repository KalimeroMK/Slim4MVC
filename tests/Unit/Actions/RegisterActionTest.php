<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Auth\RegisterAction;
use App\DTO\Auth\RegisterDTO;
use App\Models\User;
use App\Support\Mailer;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class RegisterActionTest extends TestCase
{
    private RegisterAction $action;

    private MockObject $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->createMock(Mailer::class);
        $this->action = new RegisterAction($this->mailer);
    }

    public function test_execute_creates_user_with_hashed_password(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->anything(),
                'Welcome to our platform!',
                'email.welcome',
                $this->anything()
            )
            ->willReturn(true);

        $dto = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $user = $this->action->execute($dto);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    public function test_execute_sends_welcome_email(): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(
                'test@example.com',
                'Welcome to our platform!',
                'email.welcome',
                $this->callback(function ($data) {
                    return isset($data['user']) && $data['user'] instanceof User;
                })
            )
            ->willReturn(true);

        $dto = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $this->action->execute($dto);
    }

    public function test_execute_creates_user_in_database(): void
    {
        $this->mailer->method('send')->willReturn(true);

        $dto = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $user = $this->action->execute($dto);

        $this->assertNotNull($user->id);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }
}
