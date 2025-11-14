<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    public function test_role_can_have_users(): void
    {
        $role = Role::create(['name' => 'admin']);

        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $role->users()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $role->users);
        $this->assertTrue($role->users->contains('id', $user1->id));
        $this->assertTrue($role->users->contains('id', $user2->id));
    }

    public function test_role_can_have_permissions(): void
    {
        $role = Role::create(['name' => 'admin']);

        $permission1 = Permission::create(['name' => 'create-users']);
        $permission2 = Permission::create(['name' => 'edit-users']);

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains('name', 'create-users'));
        $this->assertTrue($role->permissions->contains('name', 'edit-users'));
    }

    public function test_sync_permissions_replaces_existing_permissions(): void
    {
        $role = Role::create(['name' => 'admin']);

        $permission1 = Permission::create(['name' => 'create-users']);
        $permission2 = Permission::create(['name' => 'edit-users']);
        $permission3 = Permission::create(['name' => 'delete-users']);

        // Initially assign permission1 and permission2
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        $this->assertCount(2, $role->permissions);

        // Sync with permission2 and permission3
        $role->syncPermissions([$permission2->id, $permission3->id]);

        $role->refresh();
        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains('name', 'edit-users'));
        $this->assertTrue($role->permissions->contains('name', 'delete-users'));
        $this->assertFalse($role->permissions->contains('name', 'create-users'));
    }
}
