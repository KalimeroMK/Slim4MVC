<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTO\User\UpdateUserDTO;
use App\Interface\User\UpdateUserActionInterface;
use App\Repositories\UserRepository;

final class UpdateUserAction implements UpdateUserActionInterface
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute user update.
     *
     * @param UpdateUserDTO $dto
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
