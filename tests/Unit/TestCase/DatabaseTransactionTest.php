<?php

declare(strict_types=1);

namespace Tests\Unit\TestCase;

use Tests\TestCase;

/**
 * Test that database transactions work correctly in TestCase.
 */
class DatabaseTransactionTest extends TestCase
{
    public function test_database_transaction_rolls_back_after_test(): void
    {
        // Create a user in this test
        $user = $this->createUser(['email' => 'transaction-test@example.com']);

        // Verify user exists
        $this->assertDatabaseHas('users', ['email' => 'transaction-test@example.com']);

        // After tearDown, transaction should rollback
        // This is verified by the fact that the next test doesn't see this user
    }

    public function test_database_is_clean_after_previous_test(): void
    {
        // This test should not see the user from the previous test
        // because transactions rollback after each test
        $user = \App\Modules\User\Infrastructure\Models\User::where('email', 'transaction-test@example.com')->first();

        $this->assertNull($user, 'Database should be clean after transaction rollback');
    }

    public function test_multiple_creations_in_single_test(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);
        $role = $this->createRole(['name' => 'test-role']);

        $this->assertDatabaseHas('users', ['email' => 'user1@test.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@test.com']);
        $this->assertDatabaseHas('roles', ['name' => 'test-role']);
    }

    public function test_database_is_clean_after_multiple_creations(): void
    {
        // This test should not see users/roles from previous test
        $user1 = \App\Modules\User\Infrastructure\Models\User::where('email', 'user1@test.com')->first();
        $user2 = \App\Modules\User\Infrastructure\Models\User::where('email', 'user2@test.com')->first();
        $role = \App\Modules\Role\Infrastructure\Models\Role::where('name', 'test-role')->first();

        $this->assertNull($user1);
        $this->assertNull($user2);
        $this->assertNull($role);
    }
}
