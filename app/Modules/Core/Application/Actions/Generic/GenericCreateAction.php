<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Exceptions\BadRequestException;
use App\Modules\Core\Infrastructure\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic Create Action that works with any repository.
 *
 * @template TModel of Model
 */
final class GenericCreateAction
{
    /**
     * @param Repository<TModel> $repository
     */
    public function __construct(
        private readonly Repository $repository
    ) {}

    /**
     * Execute the create action.
     *
     * @param array<string, mixed> $data
     * @return TModel
     *
     * @throws BadRequestException
     */
    public function execute(array $data): Model
    {
        if (empty($data)) {
            throw new BadRequestException('Cannot create entity with empty data');
        }

        return $this->repository->create($data);
    }
}
