<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Role;
use App\Repositories\RoleRepository;
use Tests\TestCase;

class RoleRepositoryTest extends TestCase
{
    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RoleRepository();
    }

    public function test_all_returns_all_roles(): void
    {
        Role::create(['name' => 'Role 1']);
        Role::create(['name' => 'Role 2']);

        $roles = $this->repository->all();

        $this->assertCount(2, $roles);
    }

    public function test_find_returns_role_by_id(): void
    {
        $role = Role::create(['name' => 'Test Role']);

        $found = $this->repository->find($role->id);

        $this->assertNotNull($found);
        $this->assertEquals($role->id, $found->id);
        $this->assertEquals('Test Role', $found->name);
    }

    public function test_create_creates_new_role(): void
    {
        $role = $this->repository->create(['name' => 'New Role']);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('New Role', $role->name);
        $this->assertDatabaseHas('roles', ['name' => 'New Role']);
    }

    public function test_update_updates_role(): void
    {
        $role = Role::create(['name' => 'Old Name']);

        $updated = $this->repository->update($role->id, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'New Name']);
    }

    public function test_delete_deletes_role(): void
    {
        $role = Role::create(['name' => 'To Delete']);

        $result = $this->repository->delete($role->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_find_by_name_returns_role(): void
    {
        $role = Role::create(['name' => 'Find Me']);

        $found = $this->repository->findByName('Find Me');

        $this->assertNotNull($found);
        $this->assertEquals($role->id, $found->id);
    }

    public function test_find_by_name_returns_null_when_not_found(): void
    {
        $found = $this->repository->findByName('Nonexistent');

        $this->assertNull($found);
    }
}
