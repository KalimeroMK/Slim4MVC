<?php

declare(strict_types=1);

// src/Actions/Auth/ResetPasswordAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\ResetPasswordDTO;
use App\Exceptions\NotFoundException;
use App\Interface\Auth\ResetPasswordActionInterface;
use App\Repositories\UserRepository;

class ResetPasswordAction implements ResetPasswordActionInterface
{
    public function __construct(
        protected UserRepository $repository
    ) {}

    /**
     * Execute password reset.
     *
     * @param ResetPasswordDTO $dto
     * @return void
     * @throws NotFoundException
     */
    public function execute(ResetPasswordDTO $dto): void
    {
        $user = $this->repository->findByPasswordResetToken($dto->token);

        if (! $user) {
            throw new NotFoundException('Invalid or expired reset token');
        }

        $this->repository->update($user->id, [
            'password' => password_hash($dto->password, PASSWORD_BCRYPT),
            'password_reset_token' => null,
        ]);
    }
}
