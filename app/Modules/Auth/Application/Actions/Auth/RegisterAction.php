<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\Interfaces\Auth\RegisterActionInterface;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Events\UserRegistered;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class RegisterAction implements RegisterActionInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private UserRepository $userRepository
    ) {}

    /**
     * Execute registration action.
     */
    public function execute(RegisterDTO $registerDTO): User
    {
        $user = $this->userRepository->create([
            'name' => $registerDTO->name,
            'email' => $registerDTO->email,
            'password' => password_hash($registerDTO->password, PASSWORD_BCRYPT),
        ]);

        $this->dispatcher->dispatch(new UserRegistered($user));

        return $user;
    }
}
