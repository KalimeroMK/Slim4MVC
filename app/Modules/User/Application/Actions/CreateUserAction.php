<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Actions;

use App\Modules\User\Application\DTOs\CreateUserDTO;
use App\Modules\User\Application\Interfaces\CreateUserActionInterface;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class CreateUserAction implements CreateUserActionInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Execute user creation.
     *
     * @return array<string, mixed>
     */
    public function execute(CreateUserDTO $createUserDTO): array
    {
        // Hash the password and create user
        $user = $this->userRepository->create([
            'name' => $createUserDTO->name,
            'email' => $createUserDTO->email,
            'password' => password_hash($createUserDTO->password, PASSWORD_BCRYPT),
        ]);

        return $user->toArray();
    }
}
