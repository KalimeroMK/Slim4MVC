<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Modules\Permission\Infrastructure\Database\Factories\PermissionFactory;
use App\Modules\Permission\Infrastructure\Models\Permission;
use Tests\TestCase;

final class PermissionFactoryTest extends TestCase
{
    public function test_factory_creates_permission_with_default_attributes(): void
    {
        $permissionFactory = new PermissionFactory();
        $permission = $permissionFactory->create();

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertNotNull($permission->id);
        $this->assertNotNull($permission->name);
        $this->assertStringContainsString('-', (string) $permission->name);
    }

    public function test_factory_creates_permission_with_custom_attributes(): void
    {
        $permissionFactory = new PermissionFactory();
        $permission = $permissionFactory->create(['name' => 'custom-permission']);

        $this->assertEquals('custom-permission', $permission->name);
    }

    public function test_factory_with_name(): void
    {
        $permissionFactory = new PermissionFactory();
        $permission = $permissionFactory->withName('test-permission')->create();

        $this->assertEquals('test-permission', $permission->name);
    }

    public function test_factory_creates_many_permissions(): void
    {
        $permissions = [];
        for ($i = 0; $i < 4; ++$i) {
            $factory = new PermissionFactory();
            $permissions[] = $factory->withName('permission-'.uniqid())->create();
        }

        $this->assertCount(4, $permissions);
        $this->assertContainsOnlyInstancesOf(Permission::class, $permissions);
    }
}
