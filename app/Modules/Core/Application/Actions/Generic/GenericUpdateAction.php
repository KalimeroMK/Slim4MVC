<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Actions\Generic;

use App\Modules\Core\Infrastructure\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic Update Action that works with any repository.
 *
 * @template TModel of Model
 */
final readonly class GenericUpdateAction
{
    /**
     * @param  Repository<TModel>  $repository
     */
    public function __construct(
        private Repository $repository
    ) {}

    /**
     * Execute the update action.
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function execute(int|string $id, array $data): Model
    {
        if ($data === []) {
            /** @var TModel $result */
            $result = $this->repository->findOrFail((int) $id);

            return $result;
        }

        /** @var TModel $result */
        $result = $this->repository->update((int) $id, $data);

        return $result;
    }
}
