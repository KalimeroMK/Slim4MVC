<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Permission;
use App\Repositories\PermissionRepository;
use Tests\TestCase;

class PermissionRepositoryTest extends TestCase
{
    private PermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PermissionRepository();
    }

    public function test_all_returns_all_permissions(): void
    {
        Permission::create(['name' => 'Permission 1']);
        Permission::create(['name' => 'Permission 2']);

        $permissions = $this->repository->all();

        $this->assertCount(2, $permissions);
    }

    public function test_find_returns_permission_by_id(): void
    {
        $permission = Permission::create(['name' => 'Test Permission']);

        $found = $this->repository->find($permission->id);

        $this->assertNotNull($found);
        $this->assertEquals($permission->id, $found->id);
        $this->assertEquals('Test Permission', $found->name);
    }

    public function test_create_creates_new_permission(): void
    {
        $permission = $this->repository->create(['name' => 'New Permission']);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('New Permission', $permission->name);
        $this->assertDatabaseHas('permissions', ['name' => 'New Permission']);
    }

    public function test_update_updates_permission(): void
    {
        $permission = Permission::create(['name' => 'Old Name']);

        $updated = $this->repository->update($permission->id, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'New Name']);
    }

    public function test_delete_deletes_permission(): void
    {
        $permission = Permission::create(['name' => 'To Delete']);

        $result = $this->repository->delete($permission->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_find_by_name_returns_permission(): void
    {
        $permission = Permission::create(['name' => 'Find Me']);

        $found = $this->repository->findByName('Find Me');

        $this->assertNotNull($found);
        $this->assertEquals($permission->id, $found->id);
    }

    public function test_find_by_name_returns_null_when_not_found(): void
    {
        $found = $this->repository->findByName('Nonexistent');

        $this->assertNull($found);
    }
}
