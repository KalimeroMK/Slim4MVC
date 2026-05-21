<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Auth\Application\Interfaces\Auth\PasswordRecoveryActionInterface;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Events\PasswordResetRequested;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class PasswordRecoveryAction implements PasswordRecoveryActionInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private Dispatcher $dispatcher
    ) {}

    /**
     * Execute password recovery action.
     */
    public function execute(PasswordRecoveryDTO $passwordRecoveryDTO): void
    {
        $user = $this->userRepository->findByEmail($passwordRecoveryDTO->email);

        if (! $user instanceof \App\Modules\User\Infrastructure\Models\User) {
            // Don't reveal if email exists for security
            return;
        }

        $token = bin2hex(random_bytes(32));
        $user->password_reset_token = hash('sha256', $token);
        $user->password_reset_token_expires_at = date('Y-m-d H:i:s', time() + 3600);
        $user->save();

        // Dispatch with raw token so the email link contains the unhashed value.
        // Only the hash is persisted — a DB leak cannot be used directly.
        $this->dispatcher->dispatch(new PasswordResetRequested($user, $token));
    }
}
