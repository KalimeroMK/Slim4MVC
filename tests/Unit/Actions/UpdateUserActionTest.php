<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Application\Actions\UpdateUserAction;
use App\Modules\User\Application\DTOs\UpdateUserDTO;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Tests\TestCase;

final class UpdateUserActionTest extends TestCase
{
    private UpdateUserAction $updateUserAction;

    protected function setUp(): void
    {
        parent::setUp();
        $userRepository = new UserRepository();
        $this->updateUserAction = new UpdateUserAction($userRepository);
    }

    public function test_execute_updates_user_name(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'update-test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $updateUserDTO = new UpdateUserDTO(
            id: $user->id,
            name: 'Updated Name',
        );

        $updatedUser = $this->updateUserAction->execute($updateUserDTO);

        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_execute_updates_user_email(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'original@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $updateUserDTO = new UpdateUserDTO(
            id: $user->id,
            email: 'updated@example.com',
        );

        $updatedUser = $this->updateUserAction->execute($updateUserDTO);

        $this->assertEquals('updated@example.com', $updatedUser->email);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'updated@example.com',
        ]);
    }

    public function test_execute_updates_user_password(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'password-test@example.com',
            'password' => password_hash('oldpassword', PASSWORD_BCRYPT),
        ]);

        $updateUserDTO = new UpdateUserDTO(
            id: $user->id,
            password: 'newpassword123',
        );

        $updatedUser = $this->updateUserAction->execute($updateUserDTO);

        $this->assertTrue(password_verify('newpassword123', (string) $updatedUser->password));
    }

    public function test_execute_syncs_user_roles(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'roles-test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $role = Role::create(['name' => 'test-role']);

        $updateUserDTO = new UpdateUserDTO(
            id: $user->id,
            roles: [$role->id],
        );

        $updatedUser = $this->updateUserAction->execute($updateUserDTO);

        $this->assertTrue($updatedUser->roles->contains($role->id));
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_execute_clears_user_roles_when_empty_array(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'clear-roles@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $role = Role::create(['name' => 'test-role']);
        $user->roles()->attach($role->id);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        $updateUserDTO = new UpdateUserDTO(
            id: $user->id,
            roles: [],
        );

        $this->updateUserAction->execute($updateUserDTO);

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_execute_loads_roles_in_response(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'loads-roles@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $role = Role::create(['name' => 'test-role']);

        $updateUserDTO = new UpdateUserDTO(
            id: $user->id,
            roles: [$role->id],
        );

        $updatedUser = $this->updateUserAction->execute($updateUserDTO);

        $this->assertTrue($updatedUser->relationLoaded('roles'));
    }
}
