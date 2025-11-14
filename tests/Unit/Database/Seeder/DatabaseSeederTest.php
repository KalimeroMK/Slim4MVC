<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Seeder;

use Database\Seed\DatabaseSeeder;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_seeder_creates_roles(): void
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
        $this->assertDatabaseHas('roles', ['name' => 'user']);
    }

    public function test_seeder_creates_permissions(): void
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

        $this->assertDatabaseHas('permissions', ['name' => 'list-admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'create-manager']);
        $this->assertDatabaseHas('permissions', ['name' => 'delete-user']);
    }

    public function test_seeder_creates_users(): void
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

        $this->assertDatabaseHas('users', ['email' => 'admin@demo.com']);
        $this->assertDatabaseHas('users', ['email' => 'manager@demo.com']);
        $this->assertDatabaseHas('users', ['email' => 'user@demo.com']);
    }

    public function test_seeder_assigns_roles_to_users(): void
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

        $adminUser = \App\Modules\User\Infrastructure\Models\User::where('email', 'admin@demo.com')->first();
        $this->assertNotNull($adminUser);
        $this->assertTrue($adminUser->roles->contains('name', 'admin'));

        $managerUser = \App\Modules\User\Infrastructure\Models\User::where('email', 'manager@demo.com')->first();
        $this->assertNotNull($managerUser);
        $this->assertTrue($managerUser->roles->contains('name', 'manager'));
    }

    public function test_seeder_attaches_permissions_to_roles(): void
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

        $adminRole = \App\Modules\Role\Infrastructure\Models\Role::where('name', 'admin')->first();
        $this->assertNotNull($adminRole);
        $this->assertGreaterThan(0, $adminRole->permissions->count());
    }

    public function test_seeder_creates_additional_fake_users(): void
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

        $userCount = \App\Modules\User\Infrastructure\Models\User::count();
        $this->assertGreaterThanOrEqual(13, $userCount); // 3 demo + 10 fake
    }
}
