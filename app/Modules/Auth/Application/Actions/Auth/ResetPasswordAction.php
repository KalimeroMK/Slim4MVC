<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use App\Modules\Auth\Application\Interfaces\Auth\ResetPasswordActionInterface;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final readonly class ResetPasswordAction implements ResetPasswordActionInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Execute password reset action.
     *
     * @throws NotFoundException
     */
    public function execute(ResetPasswordDTO $resetPasswordDTO): void
    {
        $user = $this->userRepository->findByPasswordResetToken($resetPasswordDTO->token);

        if (! $user instanceof \App\Modules\User\Infrastructure\Models\User) {
            throw new NotFoundException('Invalid or expired reset token');
        }

        $user->password = password_hash($resetPasswordDTO->password, PASSWORD_BCRYPT);
        $user->password_reset_token = null;
        $user->save();
    }
}
