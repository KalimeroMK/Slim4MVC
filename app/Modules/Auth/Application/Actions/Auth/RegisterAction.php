<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\Interfaces\Auth\RegisterActionInterface;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Events\UserRegistered;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class RegisterAction implements RegisterActionInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute registration action.
     */
    public function execute(RegisterDTO $dto): User
    {
        $user = $this->repository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => password_hash($dto->password, PASSWORD_BCRYPT),
        ]);

        $this->dispatcher->dispatch(new UserRegistered($user));

        return $user;
    }
}
