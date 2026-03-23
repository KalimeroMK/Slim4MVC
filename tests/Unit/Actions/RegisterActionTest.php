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

class RegisterActionTest extends TestCase
{
    private RegisterAction $action;

    private MockObject $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(Dispatcher::class);
        $repository = new UserRepository();
        $this->action = new RegisterAction($this->dispatcher, $repository);
    }

    public function test_execute_creates_user_with_hashed_password(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UserRegistered::class));

        $dto = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $user = $this->action->execute($dto);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    public function test_execute_dispatches_user_registered_event(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (UserRegistered $event) {
                return $event->user instanceof User
                    && $event->user->email === 'test@example.com';
            }));

        $dto = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $this->action->execute($dto);
    }

    public function test_execute_creates_user_in_database(): void
    {
        $this->dispatcher->method('dispatch');

        $dto = new RegisterDTO('Test User', 'test@example.com', 'password123');
        $user = $this->action->execute($dto);

        $this->assertNotNull($user->id);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }
}
