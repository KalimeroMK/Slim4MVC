<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTO\Auth\PasswordRecoveryDTO;
use App\Events\Dispatcher;
use App\Events\PasswordResetRequested;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Repositories\UserRepository;
use Random\RandomException;

class PasswordRecoveryAction implements PasswordRecoveryActionInterface
{
    public function __construct(
        protected Dispatcher $dispatcher,
        protected UserRepository $repository
    ) {}

    /**
     * Execute password recovery request.
     *
     * @throws RandomException
     */
    public function execute(PasswordRecoveryDTO $dto): void
    {
        $user = $this->repository->findByEmail($dto->email);

        if (! $user instanceof \App\Models\User) {
            return; // Don't reveal if user exists
        }

        $resetToken = bin2hex(random_bytes(16));
        $this->repository->update($user->id, [
            'password_reset_token' => $resetToken,
        ]);

        // Reload user to get updated token
        $user = $this->repository->findOrFail($user->id);

        // Dispatch event instead of sending email directly
        $this->dispatcher->dispatch(new PasswordResetRequested($user, $resetToken));
    }
}
