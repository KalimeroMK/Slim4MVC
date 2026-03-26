<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Core\Application\Actions\Generic\CrudActionFactory;
use App\Modules\Core\Application\Actions\Generic\GenericCreateAction;
use App\Modules\Core\Application\Actions\Generic\GenericDeleteAction;
use App\Modules\Core\Application\Actions\Generic\GenericGetAction;
use App\Modules\Core\Application\Actions\Generic\GenericListAction;
use App\Modules\Core\Application\Actions\Generic\GenericUpdateAction;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Generic CRUD Actions.
 * 
 * Tests with real database interactions.
 * 
 * @covers \App\Modules\Core\Application\Actions\Generic\CrudActionFactory
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericCreateAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericUpdateAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericDeleteAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericGetAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericListAction
 */
class GenericCrudIntegrationTest extends TestCase
{
    private ?UserRepository $repository = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip if no database available or tables don't exist
        try {
            $this->repository = new UserRepository();
            // Try to query to check if table exists
            User::first();
        } catch (\Exception $e) {
            $this->markTestSkipped('Database not available or tables not created: ' . $e->getMessage());
        }
    }

    /**
     */
    public function test_it_creates_entity_through_generic_action(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $action = new GenericCreateAction($this->repository);

        $data = [
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password123',
        ];

        $user = $action->execute($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data['name'], $user->name);
        $this->assertEquals($data['email'], $user->email);
        $this->assertNotNull($user->id);

        // Cleanup
        $user->delete();
    }

    /**
     */
    public function test_it_updates_entity_through_generic_action(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        // First create a user
        $createAction = new GenericCreateAction($this->repository);
        $user = $createAction->execute([
            'name' => 'Original Name',
            'email' => 'update_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $updateAction = new GenericUpdateAction($this->repository);
        $updated = $updateAction->execute($user->id, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals($user->email, $updated->email);

        // Cleanup
        $updated->delete();
    }

    /**
     */
    public function test_it_deletes_entity_through_generic_action(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        // First create a user
        $createAction = new GenericCreateAction($this->repository);
        $user = $createAction->execute([
            'name' => 'To Delete',
            'email' => 'delete_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $userId = $user->id;

        $deleteAction = new GenericDeleteAction($this->repository);
        $deleteAction->execute($userId);

        // Verify deletion
        $getAction = new GenericGetAction($this->repository);
        
        $this->expectException(NotFoundException::class);
        $getAction->execute($userId);
    }

    /**
     */
    public function test_it_gets_entity_through_generic_action(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        // First create a user
        $createAction = new GenericCreateAction($this->repository);
        $user = $createAction->execute([
            'name' => 'Get Test',
            'email' => 'get_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $getAction = new GenericGetAction($this->repository);
        $found = $getAction->execute($user->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals($user->email, $found->email);

        // Cleanup
        $found->delete();
    }

    /**
     */
    public function test_it_lists_entities_through_generic_action(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $listAction = new GenericListAction($this->repository);
        $result = $listAction->execute(1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('perPage', $result);

        $this->assertTrue(is_array($result['items']) || $result['items'] instanceof Collection, 'Items should be an array or Collection');
        $this->assertIsInt($result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['perPage']);
    }

    /**
     */
    public function test_it_throws_when_getting_nonexistent_entity(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $getAction = new GenericGetAction($this->repository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Resource not found');

        $getAction->execute(999999);
    }

    /**
     */
    public function test_it_throws_when_updating_nonexistent_entity(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $updateAction = new GenericUpdateAction($this->repository);

        $this->expectException(\Exception::class);

        $updateAction->execute(999999, ['name' => 'New Name']);
    }

    /**
     */
    public function test_it_throws_when_deleting_nonexistent_entity(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $deleteAction = new GenericDeleteAction($this->repository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Resource not found');

        $deleteAction->execute(999999);
    }

    /**
     */
    public function test_it_gets_all_entities(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $listAction = new GenericListAction($this->repository);
        $all = $listAction->all();

        $this->assertInstanceOf(Collection::class, $all);
    }

    /**
     */
    public function test_it_throws_when_creating_with_empty_data(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $createAction = new GenericCreateAction($this->repository);

        $this->expectException(\App\Modules\Core\Infrastructure\Exceptions\BadRequestException::class);
        $this->expectExceptionMessage('Cannot create entity with empty data');

        $createAction->execute([]);
    }

    /**
     */
    public function test_it_returns_existing_model_when_updating_with_empty_data(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        // Create user
        $createAction = new GenericCreateAction($this->repository);
        $user = $createAction->execute([
            'name' => 'Empty Update Test',
            'email' => 'empty_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        // Update with empty data should return existing model
        $updateAction = new GenericUpdateAction($this->repository);
        $result = $updateAction->execute($user->id, []);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);

        // Cleanup
        $result->delete();
    }

    /**
     */
    public function test_it_uses_factory_to_create_actions(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $factory = new CrudActionFactory($this->repository);

        $this->assertInstanceOf(GenericCreateAction::class, $factory->create());
        $this->assertInstanceOf(GenericUpdateAction::class, $factory->update());
        $this->assertInstanceOf(GenericDeleteAction::class, $factory->delete());
        $this->assertInstanceOf(GenericGetAction::class, $factory->get());
        $this->assertInstanceOf(GenericListAction::class, $factory->list());
    }

    /**
     */
    public function test_it_returns_all_actions_from_factory(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $factory = new CrudActionFactory($this->repository);
        $all = $factory->all();

        $this->assertArrayHasKey('create', $all);
        $this->assertArrayHasKey('update', $all);
        $this->assertArrayHasKey('delete', $all);
        $this->assertArrayHasKey('get', $all);
        $this->assertArrayHasKey('list', $all);
        $this->assertArrayHasKey('repository', $all);
    }

    /**
     */
    public function test_it_returns_repository_from_factory(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $factory = new CrudActionFactory($this->repository);
        
        $this->assertSame($this->repository, $factory->getRepository());
    }

    /**
     */
    public function test_it_paginates_with_custom_per_page(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $listAction = new GenericListAction($this->repository, 5);
        $result = $listAction->execute(1);

        $this->assertEquals(5, $result['perPage']);
    }

    /**
     */
    public function test_it_loads_relations_when_executing_get_with(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        // Create user
        $createAction = new GenericCreateAction($this->repository);
        $user = $createAction->execute([
            'name' => 'Relation Test',
            'email' => 'relation_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $getAction = new GenericGetAction($this->repository);
        $found = $getAction->executeWith($user->id, ['roles']);

        $this->assertInstanceOf(User::class, $found);
        
        // Cleanup
        $found->delete();
    }

    /**
     */
    public function test_it_ignores_filters_in_execute_with_filters(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $listAction = new GenericListAction($this->repository);
        
        // Filters are currently ignored, but method should work
        $result = $listAction->executeWithFilters(['status' => 'active'], 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
    }

    /**
     */
    public function test_it_loads_relations_in_execute_with_for_list(): void
    {
        if ($this->repository === null) {
            $this->markTestSkipped('Repository not available');
        }
        
        $listAction = new GenericListAction($this->repository);
        $result = $listAction->executeWith(['roles'], 1, 5);

        $this->assertIsArray($result);
        $this->assertInstanceOf(Collection::class, $result['items']);
    }
}
