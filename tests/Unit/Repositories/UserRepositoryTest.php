<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    public function test_all_returns_all_users(): void
    {
        User::create(['name' => 'User 1', 'email' => 'user1@test.com', 'password' => 'password']);
        User::create(['name' => 'User 2', 'email' => 'user2@test.com', 'password' => 'password']);

        $users = $this->repository->all();

        $this->assertCount(2, $users);
    }

    public function test_find_returns_user_by_id(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com', 'password' => 'password']);

        $found = $this->repository->find($user->id);

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals('Test User', $found->name);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $found = $this->repository->find(999);

        $this->assertNull($found);
    }

    public function test_find_or_fail_returns_user(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com', 'password' => 'password']);

        $found = $this->repository->findOrFail($user->id);

        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_or_fail_throws_exception_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->findOrFail(999);
    }

    public function test_create_creates_new_user(): void
    {
        $user = $this->repository->create([
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('New User', $user->name);
        $this->assertEquals('new@test.com', $user->email);
        $this->assertDatabaseHas('users', ['email' => 'new@test.com']);
    }

    public function test_update_updates_user(): void
    {
        $user = User::create(['name' => 'Old Name', 'email' => 'old@test.com', 'password' => 'password']);

        $updated = $this->repository->update($user->id, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_delete_deletes_user(): void
    {
        $user = User::create(['name' => 'To Delete', 'email' => 'delete@test.com', 'password' => 'password']);

        $result = $this->repository->delete($user->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_paginate_returns_paginated_results(): void
    {
        User::create(['name' => 'User 1', 'email' => 'user1@test.com', 'password' => 'password']);
        User::create(['name' => 'User 2', 'email' => 'user2@test.com', 'password' => 'password']);

        $result = $this->repository->paginate(1, 1);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['perPage']);
        $this->assertCount(1, $result['items']);
    }

    public function test_find_by_email_returns_user(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'find@test.com', 'password' => 'password']);

        $found = $this->repository->findByEmail('find@test.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_find_by_email_returns_null_when_not_found(): void
    {
        $found = $this->repository->findByEmail('nonexistent@test.com');

        $this->assertNull($found);
    }

    public function test_find_by_password_reset_token_returns_user(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'token@test.com',
            'password' => 'password',
            'password_reset_token' => 'test-token-123',
        ]);

        $found = $this->repository->findByPasswordResetToken('test-token-123');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_paginate_with_roles_includes_roles(): void
    {
        $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => 'password']);

        $result = $this->repository->paginateWithRoles(1, 15);

        $this->assertArrayHasKey('items', $result);
        $this->assertTrue($result['total'] >= 1);
    }
}

