<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Modules\Permission\Infrastructure\Database\Factories\PermissionFactory;
use App\Modules\Permission\Infrastructure\Models\Permission;
use Tests\TestCase;

class PermissionFactoryTest extends TestCase
{
    public function test_factory_creates_permission_with_default_attributes(): void
    {
        $factory = new PermissionFactory();
        $permission = $factory->create();

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertNotNull($permission->id);
        $this->assertNotNull($permission->name);
        $this->assertStringContainsString('-', $permission->name);
    }

    public function test_factory_creates_permission_with_custom_attributes(): void
    {
        $factory = new PermissionFactory();
        $permission = $factory->create(['name' => 'custom-permission']);

        $this->assertEquals('custom-permission', $permission->name);
    }

    public function test_factory_with_name(): void
    {
        $factory = new PermissionFactory();
        $permission = $factory->withName('test-permission')->create();

        $this->assertEquals('test-permission', $permission->name);
    }

    public function test_factory_creates_many_permissions(): void
    {
        $factory = new PermissionFactory();
        $permissions = $factory->createMany(4);

        $this->assertCount(4, $permissions);
        foreach ($permissions as $permission) {
            $this->assertInstanceOf(Permission::class, $permission);
        }
    }
}
