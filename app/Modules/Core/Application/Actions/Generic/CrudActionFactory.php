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
final readonly class CrudActionFactory
{
    /**
     * @param Repository<TModel> $repository
     */
    public function __construct(
        private Repository $repository
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
        return new GenericCreateAction($this->repository);
    }

    /**
     * @return GenericUpdateAction<TModel>
     */
    public function update(): GenericUpdateAction
    {
        return new GenericUpdateAction($this->repository);
    }

    /**
     * @return GenericDeleteAction<TModel>
     */
    public function delete(): GenericDeleteAction
    {
        return new GenericDeleteAction($this->repository);
    }

    /**
     * @return GenericGetAction<TModel>
     */
    public function get(): GenericGetAction
    {
        return new GenericGetAction($this->repository);
    }

    /**
     * @return GenericListAction<TModel>
     */
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
