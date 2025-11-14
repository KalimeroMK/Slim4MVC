<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\Dispatcher;
use App\Events\Event;
use App\Events\PasswordResetRequested;
use App\Events\UserRegistered;
use App\Models\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DispatcherTest extends TestCase
{
    private Dispatcher $dispatcher;
    private MockObject $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->dispatcher = new Dispatcher($this->container);
    }

    public function test_listen_registers_listener(): void
    {
        $this->dispatcher->listen(UserRegistered::class, 'TestListener');

        $listeners = $this->dispatcher->getListeners();

        $this->assertArrayHasKey(UserRegistered::class, $listeners);
        $this->assertContains('TestListener', $listeners[UserRegistered::class]);
    }

    public function test_listen_registers_multiple_listeners(): void
    {
        $this->dispatcher->listen(UserRegistered::class, 'Listener1');
        $this->dispatcher->listen(UserRegistered::class, 'Listener2');

        $listeners = $this->dispatcher->getListeners();

        $this->assertCount(2, $listeners[UserRegistered::class]);
        $this->assertContains('Listener1', $listeners[UserRegistered::class]);
        $this->assertContains('Listener2', $listeners[UserRegistered::class]);
    }

    public function test_dispatch_calls_callable_listener(): void
    {
        $called = false;
        $receivedEvent = null;

        $this->dispatcher->listen(UserRegistered::class, function (UserRegistered $event) use (&$called, &$receivedEvent) {
            $called = true;
            $receivedEvent = $event;
        });

        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
        $event = new UserRegistered($user);

        $this->dispatcher->dispatch($event);

        $this->assertTrue($called);
        $this->assertSame($event, $receivedEvent);
    }

    public function test_dispatch_calls_class_listener(): void
    {
        $listener = $this->createMock(\App\Listeners\SendWelcomeEmail::class);
        $listener->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(UserRegistered::class));

        $this->container->method('get')
            ->with(\App\Listeners\SendWelcomeEmail::class)
            ->willReturn($listener);

        $this->dispatcher->listen(UserRegistered::class, \App\Listeners\SendWelcomeEmail::class);

        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
        $event = new UserRegistered($user);

        $this->dispatcher->dispatch($event);
    }

    public function test_dispatch_does_nothing_when_no_listeners(): void
    {
        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);
        $event = new UserRegistered($user);

        // Should not throw exception
        $this->dispatcher->dispatch($event);
        
        // Verify no listeners were called
        $listeners = $this->dispatcher->getListeners();
        $this->assertArrayNotHasKey(UserRegistered::class, $listeners);
    }

    public function test_dispatch_handles_multiple_events(): void
    {
        $userRegisteredCalled = false;
        $passwordResetCalled = false;

        $this->dispatcher->listen(UserRegistered::class, function () use (&$userRegisteredCalled) {
            $userRegisteredCalled = true;
        });

        $this->dispatcher->listen(PasswordResetRequested::class, function () use (&$passwordResetCalled) {
            $passwordResetCalled = true;
        });

        $user = new User(['name' => 'Test', 'email' => 'test@example.com']);

        $this->dispatcher->dispatch(new UserRegistered($user));
        $this->assertTrue($userRegisteredCalled);
        $this->assertFalse($passwordResetCalled);

        $this->dispatcher->dispatch(new PasswordResetRequested($user, 'token123'));
        $this->assertTrue($passwordResetCalled);
    }
}

