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
use Exception;
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
final class GenericCrudIntegrationTest extends TestCase
{
    private ?UserRepository $userRepository = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip if no database available or tables don't exist
        try {
            $this->userRepository = new UserRepository();
            // Try to query to check if table exists
            User::first();
        } catch (Exception $exception) {
            $this->markTestSkipped('Database not available or tables not created: '.$exception->getMessage());
        }
    }

    public function test_it_creates_entity_through_generic_action(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericCreateAction = new GenericCreateAction($this->userRepository);

        $data = [
            'name' => 'Test User',
            'email' => 'test_'.uniqid().'@example.com',
            'password' => 'password123',
        ];

        $model = $genericCreateAction->execute($data);

        $this->assertInstanceOf(User::class, $model);
        $this->assertEquals($data['name'], $model->name);
        $this->assertEquals($data['email'], $model->email);
        $this->assertNotNull($model->id);

        // Cleanup
        $model->delete();
    }

    public function test_it_updates_entity_through_generic_action(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        // First create a user
        $genericCreateAction = new GenericCreateAction($this->userRepository);
        $model = $genericCreateAction->execute([
            'name' => 'Original Name',
            'email' => 'update_'.uniqid().'@example.com',
            'password' => 'password123',
        ]);

        $genericUpdateAction = new GenericUpdateAction($this->userRepository);
        $updated = $genericUpdateAction->execute($model->id, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals($model->email, $updated->email);

        // Cleanup
        $updated->delete();
    }

    public function test_it_deletes_entity_through_generic_action(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        // First create a user
        $genericCreateAction = new GenericCreateAction($this->userRepository);
        $model = $genericCreateAction->execute([
            'name' => 'To Delete',
            'email' => 'delete_'.uniqid().'@example.com',
            'password' => 'password123',
        ]);

        $userId = $model->id;

        $genericDeleteAction = new GenericDeleteAction($this->userRepository);
        $genericDeleteAction->execute($userId);

        // Verify deletion
        $genericGetAction = new GenericGetAction($this->userRepository);

        $this->expectException(NotFoundException::class);
        $genericGetAction->execute($userId);
    }

    public function test_it_gets_entity_through_generic_action(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        // First create a user
        $genericCreateAction = new GenericCreateAction($this->userRepository);
        $model = $genericCreateAction->execute([
            'name' => 'Get Test',
            'email' => 'get_'.uniqid().'@example.com',
            'password' => 'password123',
        ]);

        $genericGetAction = new GenericGetAction($this->userRepository);
        $found = $genericGetAction->execute($model->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($model->id, $found->id);
        $this->assertEquals($model->email, $found->email);

        // Cleanup
        $found->delete();
    }

    public function test_it_lists_entities_through_generic_action(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericListAction = new GenericListAction($this->userRepository);
        $result = $genericListAction->execute(1, 10);

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

    public function test_it_throws_when_getting_nonexistent_entity(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericGetAction = new GenericGetAction($this->userRepository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Resource not found');

        $genericGetAction->execute(999999);
    }

    public function test_it_throws_when_updating_nonexistent_entity(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericUpdateAction = new GenericUpdateAction($this->userRepository);

        $this->expectException(Exception::class);

        $genericUpdateAction->execute(999999, ['name' => 'New Name']);
    }

    public function test_it_throws_when_deleting_nonexistent_entity(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericDeleteAction = new GenericDeleteAction($this->userRepository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Resource not found');

        $genericDeleteAction->execute(999999);
    }

    public function test_it_gets_all_entities(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericListAction = new GenericListAction($this->userRepository);
        $all = $genericListAction->all();

        $this->assertInstanceOf(Collection::class, $all);
    }

    public function test_it_throws_when_creating_with_empty_data(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericCreateAction = new GenericCreateAction($this->userRepository);

        $this->expectException(\App\Modules\Core\Infrastructure\Exceptions\BadRequestException::class);
        $this->expectExceptionMessage('Cannot create entity with empty data');

        $genericCreateAction->execute([]);
    }

    public function test_it_returns_existing_model_when_updating_with_empty_data(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        // Create user
        $genericCreateAction = new GenericCreateAction($this->userRepository);
        $model = $genericCreateAction->execute([
            'name' => 'Empty Update Test',
            'email' => 'empty_'.uniqid().'@example.com',
            'password' => 'password123',
        ]);

        // Update with empty data should return existing model
        $genericUpdateAction = new GenericUpdateAction($this->userRepository);
        $result = $genericUpdateAction->execute($model->id, []);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($model->id, $result->id);

        // Cleanup
        $result->delete();
    }

    public function test_it_uses_factory_to_create_actions(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $crudActionFactory = new CrudActionFactory($this->userRepository);

        $this->assertInstanceOf(GenericCreateAction::class, $crudActionFactory->create());
        $this->assertInstanceOf(GenericUpdateAction::class, $crudActionFactory->update());
        $this->assertInstanceOf(GenericDeleteAction::class, $crudActionFactory->delete());
        $this->assertInstanceOf(GenericGetAction::class, $crudActionFactory->get());
        $this->assertInstanceOf(GenericListAction::class, $crudActionFactory->list());
    }

    public function test_it_returns_all_actions_from_factory(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $crudActionFactory = new CrudActionFactory($this->userRepository);
        $all = $crudActionFactory->all();

        $this->assertArrayHasKey('create', $all);
        $this->assertArrayHasKey('update', $all);
        $this->assertArrayHasKey('delete', $all);
        $this->assertArrayHasKey('get', $all);
        $this->assertArrayHasKey('list', $all);
        $this->assertArrayHasKey('repository', $all);
    }

    public function test_it_returns_repository_from_factory(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $crudActionFactory = new CrudActionFactory($this->userRepository);

        $this->assertSame($this->userRepository, $crudActionFactory->getRepository());
    }

    public function test_it_paginates_with_custom_per_page(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericListAction = new GenericListAction($this->userRepository, 5);
        $result = $genericListAction->execute(1);

        $this->assertEquals(5, $result['perPage']);
    }

    public function test_it_loads_relations_when_executing_get_with(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        // Create user
        $genericCreateAction = new GenericCreateAction($this->userRepository);
        $model = $genericCreateAction->execute([
            'name' => 'Relation Test',
            'email' => 'relation_'.uniqid().'@example.com',
            'password' => 'password123',
        ]);

        $genericGetAction = new GenericGetAction($this->userRepository);
        $found = $genericGetAction->executeWith($model->id, ['roles']);

        $this->assertInstanceOf(User::class, $found);

        // Cleanup
        $found->delete();
    }

    public function test_it_ignores_filters_in_execute_with_filters(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericListAction = new GenericListAction($this->userRepository);

        // Filters are currently ignored, but method should work
        $result = $genericListAction->executeWithFilters(['status' => 'active'], 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
    }

    public function test_it_loads_relations_in_execute_with_for_list(): void
    {
        if ($this->userRepository === null) {
            $this->markTestSkipped('Repository not available');
        }

        $genericListAction = new GenericListAction($this->userRepository);
        $result = $genericListAction->executeWith(['roles'], 1, 5);

        $this->assertIsArray($result);
        $this->assertInstanceOf(Collection::class, $result['items']);
    }
}
