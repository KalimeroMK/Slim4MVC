<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Modules\Role\Infrastructure\Database\Factories\RoleFactory;
use App\Modules\Role\Infrastructure\Models\Role;
use Tests\TestCase;

class RoleFactoryTest extends TestCase
{
    public function test_factory_creates_role_with_default_attributes(): void
    {
        $factory = new RoleFactory();
        $role = $factory->create();

        $this->assertInstanceOf(Role::class, $role);
        $this->assertNotNull($role->id);
        $this->assertNotNull($role->name);
    }

    public function test_factory_creates_role_with_custom_attributes(): void
    {
        $factory = new RoleFactory();
        $role = $factory->create(['name' => 'custom-role']);

        $this->assertEquals('custom-role', $role->name);
    }

    public function test_factory_with_permissions(): void
    {
        $permission1 = $this->createPermission(['name' => 'create-users']);
        $permission2 = $this->createPermission(['name' => 'edit-users']);

        $factory = new RoleFactory();
        $role = $factory->withPermissions([$permission1->id, $permission2->id]);

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains('id', $permission1->id));
        $this->assertTrue($role->permissions->contains('id', $permission2->id));
    }

    public function test_factory_with_permissions_by_name(): void
    {
        $permission1 = $this->createPermission(['name' => 'delete-users']);
        $permission2 = $this->createPermission(['name' => 'view-users']);

        $factory = new RoleFactory();
        $role = $factory->withPermissions(['delete-users', 'view-users']);

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains('id', $permission1->id));
        $this->assertTrue($role->permissions->contains('id', $permission2->id));
    }

    public function test_factory_creates_many_roles(): void
    {
        $factory = new RoleFactory();
        $roles = $factory->createMany(3);

        $this->assertCount(3, $roles);
        foreach ($roles as $role) {
            $this->assertInstanceOf(Role::class, $role);
        }
    }
}
