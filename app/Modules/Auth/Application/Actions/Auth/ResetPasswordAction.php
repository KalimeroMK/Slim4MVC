<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Actions\Auth;

use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use App\Modules\Auth\Application\Interfaces\Auth\ResetPasswordActionInterface;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\User\Infrastructure\Repositories\UserRepository;

final class ResetPasswordAction implements ResetPasswordActionInterface
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Execute password reset action.
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

        $user->password = password_hash($dto->password, PASSWORD_BCRYPT);
        $user->password_reset_token = null;
        $user->save();
    }
}

