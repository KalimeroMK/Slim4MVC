<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    public function test_login_dto_properties(): void
    {
        $dto = new LoginDTO('test@example.com', 'password123');
        
        $this->assertEquals('test@example.com', $dto->email);
        $this->assertEquals('password123', $dto->password);
    }

    public function test_register_dto_properties(): void
    {
        $dto = new RegisterDTO('Test User', 'test@example.com', 'password123');
        
        $this->assertEquals('Test User', $dto->name);
        $this->assertEquals('test@example.com', $dto->email);
        $this->assertEquals('password123', $dto->password);
    }

    public function test_reset_password_dto_properties(): void
    {
        $dto = new ResetPasswordDTO('token123', 'newpassword');
        
        $this->assertEquals('token123', $dto->token);
        $this->assertEquals('newpassword', $dto->password);
    }

    public function test_password_recovery_dto_properties(): void
    {
        $dto = new PasswordRecoveryDTO('user@example.com');
        
        $this->assertEquals('user@example.com', $dto->email);
    }
}
