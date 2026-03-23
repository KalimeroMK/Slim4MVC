<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Application\DTOs\UpdateUserDTO;
use App\Modules\User\Application\Interfaces\UpdateUserActionInterface;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class UpdateUserAction implements UpdateUserActionInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Execute user update.
     */
    public function execute(UpdateUserDTO $updateUserDTO): User
    {
        $attributes = [];
        if ($updateUserDTO->name !== null) {
            $attributes['name'] = $updateUserDTO->name;
        }

        if ($updateUserDTO->email !== null) {
            $attributes['email'] = $updateUserDTO->email;
        }

        $this->userRepository->update($updateUserDTO->id, $attributes);

        return User::find($updateUserDTO->id);
    }
}
