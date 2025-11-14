<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Application\DTOs\CreateUserDTO;
use App\Modules\User\Application\Interfaces\CreateUserActionInterface;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class CreateUserAction implements CreateUserActionInterface
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute user creation.
     *
     * @return array<string, mixed>
     */
    public function execute(CreateUserDTO $dto): array
    {
        // Hash the password and create user
        $user = $this->repository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => password_hash($dto->password, PASSWORD_BCRYPT),
        ]);

        return $user->toArray();
    }
}
