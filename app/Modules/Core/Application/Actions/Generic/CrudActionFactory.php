<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Repositories\Repository;

/**
 * Factory for creating CRUD actions for any repository.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
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
     * @template T of \Illuminate\Database\Eloquent\Model
     * @param class-string<Repository<T>> $repositoryClass
     * @return self<T>
     */
    public static function for(string $repositoryClass): self
    {
        $repository = new $repositoryClass();

        return new self($repository);
    }

    public function create(): GenericCreateAction
    {
        return new GenericCreateAction($this->repository);
    }

    public function update(): GenericUpdateAction
    {
        return new GenericUpdateAction($this->repository);
    }

    public function delete(): GenericDeleteAction
    {
        return new GenericDeleteAction($this->repository);
    }

    public function get(): GenericGetAction
    {
        return new GenericGetAction($this->repository);
    }

    public function list(): GenericListAction
    {
        return new GenericListAction($this->repository);
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
     *   create: GenericCreateAction,
     *   update: GenericUpdateAction,
     *   delete: GenericDeleteAction,
     *   get: GenericGetAction,
     *   list: GenericListAction,
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
