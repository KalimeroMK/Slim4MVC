<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;

/**
 * Factory for creating CRUD actions for any repository.
 *
 * @template TModel of Model
 */
final class CrudActionFactory
{
    /**
     * @param Repository<TModel> $repository
     */
    public function __construct(
        private readonly Repository $repository
    ) {}

    /**
     * Create a new factory instance for a repository class.
     *
     * @template T of Model
     * @param class-string<Repository<T>> $repositoryClass
     * @return self<T>
     */
    public static function for(string $repositoryClass): self
    {
        /** @var Repository<T> $repository */
        $repository = new $repositoryClass();

        return new self($repository);
    }

    /**
     * @return GenericCreateAction<TModel>
     */
    public function create(): GenericCreateAction
    {
        /** @var GenericCreateAction<TModel> $action */
        $action = new GenericCreateAction($this->repository);

        return $action;
    }

    /**
     * @return GenericUpdateAction<TModel>
     */
    public function update(): GenericUpdateAction
    {
        /** @var GenericUpdateAction<TModel> $action */
        $action = new GenericUpdateAction($this->repository);

        return $action;
    }

    /**
     * @return GenericDeleteAction<TModel>
     */
    public function delete(): GenericDeleteAction
    {
        /** @var GenericDeleteAction<TModel> $action */
        $action = new GenericDeleteAction($this->repository);

        return $action;
    }

    /**
     * @return GenericGetAction<TModel>
     */
    public function get(): GenericGetAction
    {
        /** @var GenericGetAction<TModel> $action */
        $action = new GenericGetAction($this->repository);

        return $action;
    }

    /**
     * @return GenericListAction<TModel>
     */
    public function list(): GenericListAction
    {
        /** @var GenericListAction<TModel> $action */
        $action = new GenericListAction($this->repository);

        return $action;
    }

    /**
     * Get the underlying repository.
     *
     * @return Repository<TModel>
     */
    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * Create all CRUD actions at once.
     *
     * @return array{
     *   create: GenericCreateAction<TModel>,
     *   update: GenericUpdateAction<TModel>,
     *   delete: GenericDeleteAction<TModel>,
     *   get: GenericGetAction<TModel>,
     *   list: GenericListAction<TModel>,
     *   repository: Repository<TModel>
     * }
     */
    public function all(): array
    {
        return [
            'create' => $this->create(),
            'update' => $this->update(),
            'delete' => $this->delete(),
            'get' => $this->get(),
            'list' => $this->list(),
            'repository' => $this->repository,
        ];
    }
}
