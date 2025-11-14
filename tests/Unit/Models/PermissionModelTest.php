<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use Exception;
use Tests\TestCase;

class PermissionModelTest extends TestCase
{
    public function test_permission_can_have_roles(): void
    {
        $permission = Permission::create(['name' => 'create-users']);

        $role1 = Role::create(['name' => 'admin']);
        $role2 = Role::create(['name' => 'editor']);

        $permission->roles()->attach([$role1->id, $role2->id]);

        $this->assertCount(2, $permission->roles);
        $this->assertTrue($permission->roles->contains('name', 'admin'));
        $this->assertTrue($permission->roles->contains('name', 'editor'));
    }

    public function test_permission_name_is_unique(): void
    {
        Permission::create(['name' => 'create-users']);

        $this->expectException(Exception::class);
        Permission::create(['name' => 'create-users']);
    }
}
