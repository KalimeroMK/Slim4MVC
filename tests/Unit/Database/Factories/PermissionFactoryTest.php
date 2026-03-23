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
        $model = $permissionFactory->create();

        $this->assertInstanceOf(Permission::class, $model);
        $this->assertNotNull($model->id);
        $this->assertNotNull($model->name);
        $this->assertStringContainsString('-', (string) $model->name);
    }

    public function test_factory_creates_permission_with_custom_attributes(): void
    {
        $permissionFactory = new PermissionFactory();
        $model = $permissionFactory->create(['name' => 'custom-permission']);

        $this->assertEquals('custom-permission', $model->name);
    }

    public function test_factory_with_name(): void
    {
        $permissionFactory = new PermissionFactory();
        $model = $permissionFactory->withName('test-permission')->create();

        $this->assertEquals('test-permission', $model->name);
    }

    public function test_factory_creates_many_permissions(): void
    {
        $permissionFactory = new PermissionFactory();
        $permissions = $permissionFactory->createMany(4);

        $this->assertCount(4, $permissions);
        $this->assertContainsOnlyInstancesOf(Permission::class, $permissions);
    }
}
