<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use PHPUnit\Framework\TestCase;

final class AuthDTOsTest extends TestCase
{
    public function test_login_dto_properties(): void
    {
        $loginDTO = new LoginDTO('user@test.com', 'secret123');

        $this->assertSame('user@test.com', $loginDTO->email);
        $this->assertSame('secret123', $loginDTO->password);
    }

    public function test_register_dto_properties(): void
    {
        $registerDTO = new RegisterDTO('John Doe', 'john@test.com', 'password456');

        $this->assertSame('John Doe', $registerDTO->name);
        $this->assertSame('john@test.com', $registerDTO->email);
        $this->assertSame('password456', $registerDTO->password);
    }

    public function test_reset_password_dto_properties(): void
    {
        $resetPasswordDTO = new ResetPasswordDTO('token123', 'newpassword789');

        $this->assertSame('token123', $resetPasswordDTO->token);
        $this->assertSame('newpassword789', $resetPasswordDTO->password);
    }

    public function test_password_recovery_dto_properties(): void
    {
        $passwordRecoveryDTO = new PasswordRecoveryDTO('user@example.com');

        $this->assertSame('user@example.com', $passwordRecoveryDTO->email);
    }
}
