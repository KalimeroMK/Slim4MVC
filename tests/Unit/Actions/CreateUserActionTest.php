<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\User\CreateUserAction;
use App\DTO\User\CreateUserDTO;
use App\Models\User;
use Tests\TestCase;

class CreateUserActionTest extends TestCase
{
    private CreateUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CreateUserAction();
    }

    public function test_execute_creates_user_with_hashed_password(): void
    {
        $dto = new CreateUserDTO('Test User', 'test@example.com', 'password123');
        $result = $this->action->execute($dto);

        $this->assertIsArray($result);
        $this->assertEquals('Test User', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertArrayNotHasKey('password', $result); // Password should be hidden
    }

    public function test_execute_hashes_password_correctly(): void
    {
        $dto = new CreateUserDTO('Test User', 'test@example.com', 'password123');
        $this->action->execute($dto);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    public function test_execute_creates_user_in_database(): void
    {
        $dto = new CreateUserDTO('Test User', 'test@example.com', 'password123');
        $this->action->execute($dto);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }
}
