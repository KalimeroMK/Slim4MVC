<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use PHPUnit\Framework\TestCase;

class AuthDTOsTest extends TestCase
{
    public function test_login_dto_properties(): void
    {
        $dto = new LoginDTO('user@test.com', 'secret123');

        $this->assertEquals('user@test.com', $dto->email);
        $this->assertEquals('secret123', $dto->password);
    }

    public function test_register_dto_properties(): void
    {
        $dto = new RegisterDTO('John Doe', 'john@test.com', 'password456');

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@test.com', $dto->email);
        $this->assertEquals('password456', $dto->password);
    }

    public function test_reset_password_dto_properties(): void
    {
        $dto = new ResetPasswordDTO('token123', 'newpassword789');

        $this->assertEquals('token123', $dto->token);
        $this->assertEquals('newpassword789', $dto->password);
    }

    public function test_password_recovery_dto_properties(): void
    {
        $dto = new PasswordRecoveryDTO('user@example.com');

        $this->assertEquals('user@example.com', $dto->email);
    }
}
