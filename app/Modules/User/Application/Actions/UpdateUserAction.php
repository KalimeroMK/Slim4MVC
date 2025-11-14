<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Application\DTOs\UpdateUserDTO;
use App\Modules\User\Application\Interfaces\UpdateUserActionInterface;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class UpdateUserAction implements UpdateUserActionInterface
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute user update.
     *
     * @return array<string, mixed>
     */
    public function execute(UpdateUserDTO $dto): array
    {
        $attributes = [];
        if ($dto->name !== null) {
            $attributes['name'] = $dto->name;
        }
        if ($dto->email !== null) {
            $attributes['email'] = $dto->email;
        }

        $user = $this->repository->update($dto->id, $attributes);

        return $user->toArray();
    }
}
