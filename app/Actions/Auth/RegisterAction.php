<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterAction.php

namespace App\Actions\Auth;

use App\DTO\Auth\RegisterDTO;
use App\Interface\Auth\RegisterActionInterface;
use App\Models\User;
use App\Support\Mailer;

class RegisterAction implements RegisterActionInterface
{
    public function __construct(
        protected Mailer $mailer
    ) {}

    public function execute(RegisterDTO $dto): User
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => password_hash($dto->password, PASSWORD_BCRYPT),
        ]);

        // Send welcome email
        $this->mailer->send(
            $user->email,
            'Welcome to our platform!',
            'email.welcome',  // Blade template path
            ['user' => $user]
        );

        return $user;
    }
}
