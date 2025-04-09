<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTO\User\UpdateUserDTO;
use App\Interface\User\UpdateUserActionInterface;
use App\Models\User;

final class UpdateUserAction implements UpdateUserActionInterface
{
    /**
     * Execute user update.
     */
    public function execute(UpdateUserDTO $dto): array
    {
        $user = User::findOrFail($dto->id);

        $user->update([
            'name' => $dto->name,
            'email' => $dto->email,
        ]);

        return $user->fresh()->toArray();
    }
}
