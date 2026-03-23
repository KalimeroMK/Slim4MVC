<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\RegisterAction;
use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Events\UserRegistered;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class RegisterActionTest extends TestCase
{
    private RegisterAction $registerAction;

    private MockObject $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(Dispatcher::class);
        $userRepository = new UserRepository();
        $this->registerAction = new RegisterAction($this->dispatcher, $userRepository);
    }

    public function test_execute_creates_user_with_hashed_password(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UserRegistered::class));

        $registerDTO = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $user = $this->registerAction->execute($registerDTO);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(password_verify('password123', (string) $user->password));
    }

    public function test_execute_dispatches_user_registered_event(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (UserRegistered $userRegistered): true {
                $this->assertInstanceOf(User::class, $userRegistered->user);
                $this->assertSame('test@example.com', $userRegistered->user->email);

                return true;
            }));

        $registerDTO = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $this->registerAction->execute($registerDTO);
    }

    public function test_execute_creates_user_in_database(): void
    {
        $this->dispatcher->method('dispatch');

        $registerDTO = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $user = $this->registerAction->execute($registerDTO);

        $this->assertNotNull($user->id);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }
}
