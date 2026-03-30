<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use PHPUnit\Framework\TestCase;

final class AuthControllerTest extends TestCase
{
    public function test_login_dto_properties(): void
    {
        $loginDTO = new LoginDTO('test@example.com', 'password123');

        $this->assertSame('test@example.com', $loginDTO->email);
        $this->assertSame('password123', $loginDTO->password);
    }

    public function test_register_dto_properties(): void
    {
        $registerDTO = new RegisterDTO('Test User', 'test@example.com', 'password123');

        $this->assertSame('Test User', $registerDTO->name);
        $this->assertSame('test@example.com', $registerDTO->email);
        $this->assertSame('password123', $registerDTO->password);
    }

    public function test_reset_password_dto_properties(): void
    {
        $resetPasswordDTO = new ResetPasswordDTO('token123', 'newpassword');

        $this->assertSame('token123', $resetPasswordDTO->token);
        $this->assertSame('newpassword', $resetPasswordDTO->password);
    }

    public function test_password_recovery_dto_properties(): void
    {
        $passwordRecoveryDTO = new PasswordRecoveryDTO('user@example.com');

        $this->assertSame('user@example.com', $passwordRecoveryDTO->email);
    }
}
