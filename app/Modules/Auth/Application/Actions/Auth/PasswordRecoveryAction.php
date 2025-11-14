<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Auth\Application\Interfaces\Auth\PasswordRecoveryActionInterface;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Events\PasswordResetRequested;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class PasswordRecoveryAction implements PasswordRecoveryActionInterface
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly Dispatcher $dispatcher
    ) {}

    /**
     * Execute password recovery action.
     */
    public function execute(PasswordRecoveryDTO $dto): void
    {
        $user = $this->repository->findByEmail($dto->email);

        if (! $user instanceof \App\Modules\User\Infrastructure\Models\User) {
            // Don't reveal if email exists for security
            return;
        }

        $token = bin2hex(random_bytes(32));
        $user->password_reset_token = $token;
        $user->save();

        $this->dispatcher->dispatch(new PasswordResetRequested($user, $token));
    }
}
