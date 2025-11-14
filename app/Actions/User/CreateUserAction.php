<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTO\User\CreateUserDTO;
use App\Interface\User\CreateUserActionInterface;
use App\Repositories\UserRepository;

final class CreateUserAction implements CreateUserActionInterface
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute user creation.
     *
     * @param CreateUserDTO $dto
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
