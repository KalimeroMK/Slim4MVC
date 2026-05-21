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
use PHPUnit\Framework\MockObject\Stub;
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
    private Stub $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createStub(Repository::class);
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
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($this->createStub(Model::class));

        $model = (new GenericCreateAction($repository))->execute($data);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_create_action_throws_on_empty_data(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Cannot create entity with empty data');

        (new GenericCreateAction($this->repository))->execute([]);
    }

    public function test_update_action_updates_and_returns_model(): void
    {
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())
            ->method('update')
            ->with(1, ['name' => 'Updated'])
            ->willReturn($this->createStub(Model::class));

        $model = (new GenericUpdateAction($repository))->execute(1, ['name' => 'Updated']);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_update_action_throws_when_update_fails(): void
    {
        $this->expectException(Exception::class);
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())
            ->method('update')
            ->with(999, ['name' => 'Test'])
            ->willThrowException(new Exception('Not found'));

        (new GenericUpdateAction($repository))->execute(999, ['name' => 'Test']);
    }

    public function test_delete_action_deletes_when_found(): void
    {
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($this->createStub(Model::class));
        $repository->expects($this->once())->method('delete')->with(1);

        (new GenericDeleteAction($repository))->execute(1);

        $this->assertTrue(true);
    }

    public function test_delete_action_throws_when_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('find')->with(999)->willReturn(null);

        (new GenericDeleteAction($repository))->execute(999);
    }

    public function test_get_action_returns_model_when_found(): void
    {
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($this->createStub(Model::class));

        $model = (new GenericGetAction($repository))->execute(1);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_get_action_throws_when_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('find')->with(999)->willReturn(null);

        (new GenericGetAction($repository))->execute(999);
    }

    public function test_get_action_execute_with_uses_find_and_loads_relations(): void
    {
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($this->createStub(Model::class));

        $model = (new GenericGetAction($repository))->executeWith(1, ['roles', 'permissions']);

        $this->assertInstanceOf(Model::class, $model);
    }

    public function test_list_action_executes_paginate(): void
    {
        $expected = ['items' => new Collection(), 'total' => 100, 'page' => 1, 'perPage' => 15, 'totalPages' => 7];
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('paginate')->with(1, 15)->willReturn($expected);

        $result = (new GenericListAction($repository))->execute(1, 15);

        $this->assertEquals($expected, $result);
    }

    public function test_list_action_uses_default_per_page(): void
    {
        $expected = ['items' => new Collection(), 'total' => 100, 'page' => 1, 'perPage' => 25, 'totalPages' => 4];
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('paginate')->with(1, 25)->willReturn($expected);

        $result = (new GenericListAction($repository, 25))->execute();

        $this->assertEquals($expected, $result);
    }

    public function test_list_action_all_returns_collection(): void
    {
        $collection = new Collection();
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('all')->willReturn($collection);

        $result = (new GenericListAction($repository))->all();

        $this->assertSame($collection, $result);
    }

    public function test_list_action_execute_with_filters(): void
    {
        $expected = ['items' => new Collection(), 'total' => 50, 'page' => 1, 'perPage' => 15];
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('paginate')->with(1, 15)->willReturn($expected);

        $result = (new GenericListAction($repository))->executeWithFilters(['status' => 'active'], 1, 15);

        $this->assertEquals($expected, $result);
    }

    public function test_list_action_execute_with_uses_paginate(): void
    {
        $expected = ['items' => new Collection(), 'total' => 100, 'page' => 1, 'perPage' => 15];
        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('paginate')->with(1, 15)->willReturn($expected);

        $result = (new GenericListAction($repository))->executeWith(['category'], 1, 15);

        $this->assertEquals($expected, $result);
    }
}
