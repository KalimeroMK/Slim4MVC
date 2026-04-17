<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Role\Infrastructure\Models\Role;
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
        $user = $this->createUserAction->execute($createUserDTO);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_execute_hashes_password_correctly(): void
    {
        $createUserDTO = new CreateUserDTO('Test User', 'test@example.com', 'password123');
        $this->createUserAction->execute($createUserDTO);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertInstanceOf(User::class, $user);
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

    public function test_execute_creates_user_with_roles(): void
    {
        // Create a role first
        $role = Role::create(['name' => 'test-role']);

        $createUserDTO = new CreateUserDTO(
            name: 'Test User With Roles',
            email: 'test-with-roles@example.com',
            password: 'password123',
            roles: [$role->id]
        );

        $user = $this->createUserAction->execute($createUserDTO);

        // Check that user has the role
        $this->assertTrue($user->roles->contains($role->id));
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_execute_loads_roles_in_response(): void
    {
        $role = Role::create(['name' => 'test-role']);

        $createUserDTO = new CreateUserDTO(
            name: 'Test User',
            email: 'test-loads-roles@example.com',
            password: 'password123',
            roles: [$role->id]
        );

        $user = $this->createUserAction->execute($createUserDTO);

        // Check that roles are loaded
        $this->assertTrue($user->relationLoaded('roles'));
    }
}
