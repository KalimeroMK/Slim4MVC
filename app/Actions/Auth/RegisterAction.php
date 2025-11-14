<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\RegisterDTO;
use App\Events\Dispatcher;
use App\Events\UserRegistered;
use App\Interface\Auth\RegisterActionInterface;
use App\Models\User;
use App\Repositories\UserRepository;

class RegisterAction implements RegisterActionInterface
{
    public function __construct(
        protected Dispatcher $dispatcher,
        protected UserRepository $repository
    ) {}

    /**
     * Execute user registration.
     */
    public function execute(RegisterDTO $dto): User
    {
        $user = $this->repository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => password_hash($dto->password, PASSWORD_BCRYPT),
        ]);

        // Dispatch event instead of sending email directly
        $this->dispatcher->dispatch(new UserRegistered($user));

        return $user;
    }
}
