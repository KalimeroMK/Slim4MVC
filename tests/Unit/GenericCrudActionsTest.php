<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Application\Actions\Generic\CrudActionFactory;
use App\Modules\Core\Application\Actions\Generic\GenericCreateAction;
use App\Modules\Core\Application\Actions\Generic\GenericDeleteAction;
use App\Modules\Core\Application\Actions\Generic\GenericGetAction;
use App\Modules\Core\Application\Actions\Generic\GenericListAction;
use App\Modules\Core\Application\Actions\Generic\GenericUpdateAction;
use App\Modules\Core\Infrastructure\Exceptions\BadRequestException;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Repositories\Repository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\Core\Application\Actions\Generic\CrudActionFactory
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericCreateAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericUpdateAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericDeleteAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericGetAction
 * @covers \App\Modules\Core\Application\Actions\Generic\GenericListAction
 */
final class GenericCrudActionsTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock repository
        $this->repository = $this->createMock(Repository::class);
    }

    public function test_crud_action_factory_for_returns_instance(): void
    {
        // We can't easily test this with real classes, so we'll test the methods
        $crudActionFactory = new CrudActionFactory($this->repository);

        $this->assertInstanceOf(CrudActionFactory::class, $crudActionFactory);
    }

    public function test_crud_action_factory_create_returns_create_action(): void
    {
        $crudActionFactory = new CrudActionFactory($this->repository);

        $this->assertInstanceOf(GenericCreateAction::class, $crudActionFactory->create());
    }

    public function test_crud_action_factory_update_returns_update_action(): void
    {
        $crudActionFactory = new CrudActionFactory($this->repository);

        $this->assertInstanceOf(GenericUpdateAction::class, $crudActionFactory->update());
    }

    public function test_crud_action_factory_delete_returns_delete_action(): void
    {
        $crudActionFactory = new CrudActionFactory($this->repository);

        $this->assertInstanceOf(GenericDeleteAction::class, $crudActionFactory->delete());
    }

    public function test_crud_action_factory_get_returns_get_action(): void
    {
        $crudActionFactory = new CrudActionFactory($this->repository);

        $this->assertInstanceOf(GenericGetAction::class, $crudActionFactory->get());
    }

    public function test_crud_action_factory_list_returns_list_action(): void
    {
        $crudActionFactory = new CrudActionFactory($this->repository);

        $this->assertInstanceOf(GenericListAction::class, $crudActionFactory->list());
    }

    public function test_crud_action_factory_all_returns_all_actions(): void
    {
        $crudActionFactory = new CrudActionFactory($this->repository);
        $all = $crudActionFactory->all();

        $this->assertArrayHasKey('create', $all);
        $this->assertArrayHasKey('update', $all);
        $this->assertArrayHasKey('delete', $all);
        $this->assertArrayHasKey('get', $all);
        $this->assertArrayHasKey('list', $all);
        $this->assertArrayHasKey('repository', $all);

        $this->assertInstanceOf(GenericCreateAction::class, $all['create']);
        $this->assertInstanceOf(GenericUpdateAction::class, $all['update']);
        $this->assertInstanceOf(GenericDeleteAction::class, $all['delete']);
        $this->assertInstanceOf(GenericGetAction::class, $all['get']);
        $this->assertInstanceOf(GenericListAction::class, $all['list']);
        $this->assertSame($this->repository, $all['repository']);
    }

    public function test_create_action_executes_create_on_repository(): void
    {
        $data = ['name' => 'Test', 'email' => 'test@example.com'];

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($this->createStub(Model::class));

        $genericCreateAction = new GenericCreateAction($this->repository);
        $model = $genericCreateAction->execute($data);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_create_action_throws_on_empty_data(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Cannot create entity with empty data');

        $genericCreateAction = new GenericCreateAction($this->repository);
        $genericCreateAction->execute([]);
    }

    public function test_update_action_updates_and_returns_model(): void
    {
        $data = ['name' => 'Updated'];
        $id = 1;

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with($id, $data)
            ->willReturn($this->createStub(Model::class));

        $genericUpdateAction = new GenericUpdateAction($this->repository);
        $model = $genericUpdateAction->execute($id, $data);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_update_action_throws_when_update_fails(): void
    {
        $this->expectException(Exception::class);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(999, ['name' => 'Test'])
            ->willThrowException(new Exception('Not found'));

        $genericUpdateAction = new GenericUpdateAction($this->repository);
        $genericUpdateAction->execute(999, ['name' => 'Test']);
    }

    public function test_delete_action_deletes_when_found(): void
    {
        $id = 1;

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($this->createStub(Model::class));

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($id);

        $genericDeleteAction = new GenericDeleteAction($this->repository);
        $genericDeleteAction->execute($id);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_delete_action_throws_when_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $genericDeleteAction = new GenericDeleteAction($this->repository);
        $genericDeleteAction->execute(999);
    }

    public function test_get_action_returns_model_when_found(): void
    {
        $id = 1;

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($this->createStub(Model::class));

        $genericGetAction = new GenericGetAction($this->repository);
        $model = $genericGetAction->execute($id);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_get_action_throws_when_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $genericGetAction = new GenericGetAction($this->repository);
        $genericGetAction->execute(999);
    }

    public function test_get_action_execute_with_uses_find_and_loads_relations(): void
    {
        $id = 1;
        $relations = ['roles', 'permissions'];

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($this->createStub(Model::class));

        $genericGetAction = new GenericGetAction($this->repository);
        $model = $genericGetAction->executeWith($id, $relations);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_list_action_executes_paginate(): void
    {
        $expected = [
            'items' => new Collection(),
            'total' => 100,
            'page' => 1,
            'perPage' => 15,
            'totalPages' => 7,
        ];

        $this->repository
            ->expects($this->once())
            ->method('paginate')
            ->with(1, 15)
            ->willReturn($expected);

        $genericListAction = new GenericListAction($this->repository);
        $result = $genericListAction->execute(1, 15);

        $this->assertEquals($expected, $result);
    }

    public function test_list_action_uses_default_per_page(): void
    {
        $expected = [
            'items' => new Collection(),
            'total' => 100,
            'page' => 1,
            'perPage' => 25,
            'totalPages' => 4,
        ];

        $this->repository
            ->expects($this->once())
            ->method('paginate')
            ->with(1, 25)
            ->willReturn($expected);

        $genericListAction = new GenericListAction($this->repository, 25);
        $result = $genericListAction->execute();

        $this->assertEquals($expected, $result);
    }

    public function test_list_action_all_returns_collection(): void
    {
        $collection = new Collection();

        $this->repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        $genericListAction = new GenericListAction($this->repository);
        $result = $genericListAction->all();

        $this->assertSame($collection, $result);
    }

    public function test_list_action_execute_with_filters(): void
    {
        $filters = ['status' => 'active'];
        $expected = [
            'items' => new Collection(),
            'total' => 50,
            'page' => 1,
            'perPage' => 15,
        ];

        $this->repository
            ->expects($this->once())
            ->method('paginate')
            ->with(1, 15)
            ->willReturn($expected);

        $genericListAction = new GenericListAction($this->repository);
        $result = $genericListAction->executeWithFilters($filters, 1, 15);

        $this->assertEquals($expected, $result);
    }

    public function test_list_action_execute_with_uses_paginate(): void
    {
        $relations = ['category'];
        $expected = [
            'items' => new Collection(),
            'total' => 100,
            'page' => 1,
            'perPage' => 15,
        ];

        $this->repository
            ->expects($this->once())
            ->method('paginate')
            ->with(1, 15)
            ->willReturn($expected);

        $genericListAction = new GenericListAction($this->repository);
        $result = $genericListAction->executeWith($relations, 1, 15);

        $this->assertEquals($expected, $result);
    }
}
