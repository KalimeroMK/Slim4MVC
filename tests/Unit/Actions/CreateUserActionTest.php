<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\User\Application\Actions\CreateUserAction;
use App\Modules\User\Application\DTOs\CreateUserDTO;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Tests\TestCase;

final class CreateUserActionTest extends TestCase
{
    private CreateUserAction $createUserAction;

    protected function setUp(): void
    {
        parent::setUp();
        $userRepository = new UserRepository();
        $this->createUserAction = new CreateUserAction($userRepository);
    }

    public function test_execute_creates_user_with_hashed_password(): void
    {
        $createUserDTO = new CreateUserDTO('Test User', 'test@example.com', 'password123');
        $result = $this->createUserAction->execute($createUserDTO);

        $this->assertIsArray($result);
        $this->assertEquals('Test User', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertArrayNotHasKey('password', $result); // Password should be hidden
    }

    public function test_execute_hashes_password_correctly(): void
    {
        $createUserDTO = new CreateUserDTO('Test User', 'test@example.com', 'password123');
        $this->createUserAction->execute($createUserDTO);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(password_verify('password123', (string) $user->password));
    }

    public function test_execute_creates_user_in_database(): void
    {
        $createUserDTO = new CreateUserDTO('Test User', 'test@example.com', 'password123');
        $this->createUserAction->execute($createUserDTO);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }
}
