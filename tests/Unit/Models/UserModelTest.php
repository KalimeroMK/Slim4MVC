<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_can_have_roles(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $role = Role::create(['name' => 'admin']);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $adminRole = Role::create(['name' => 'admin']);
        $editorRole = Role::create(['name' => 'editor']);

        $user->roles()->attach([$adminRole->id, $editorRole->id]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_user_has_permission_through_role(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'create-users']);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasPermission('create-users'));
        $this->assertFalse($user->hasPermission('delete-users'));
    }

    public function test_user_permissions_returns_unique_permissions(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $role1 = Role::create(['name' => 'admin']);
        $role2 = Role::create(['name' => 'editor']);

        $permission1 = Permission::create(['name' => 'create-users']);
        $permission2 = Permission::create(['name' => 'edit-users']);

        // Both roles have permission1, only role1 has permission2
        $role1->permissions()->attach([$permission1->id, $permission2->id]);
        $role2->permissions()->attach($permission1->id);

        $user->roles()->attach([$role1->id, $role2->id]);

        $permissions = $user->permissions();

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains('name', 'create-users'));
        $this->assertTrue($permissions->contains('name', 'edit-users'));
    }

    public function test_user_password_is_hidden_in_array(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
    }
}
