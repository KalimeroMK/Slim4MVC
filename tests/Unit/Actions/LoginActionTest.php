<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\LoginAction;
use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Support\JwtService;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Tests\TestCase;

final class LoginActionTest extends TestCase
{
    private LoginAction $loginAction;

    protected function setUp(): void
    {
        parent::setUp();
        $userRepository = new UserRepository();
        $jwtService = new JwtService('test-secret-key-for-testing-only');
        $this->loginAction = new LoginAction($userRepository, $jwtService);
        $_ENV['JWT_SECRET'] = 'test-secret-key-for-testing-only';
    }

    public function test_execute_with_valid_credentials_returns_user_and_token(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $loginDTO = new LoginDTO('test@example.com', 'password123');
        $result = $this->loginAction->execute($loginDTO);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertEquals($user->email, $result['user']->email);
        $this->assertNotEmpty($result['token']);
    }

    public function test_execute_with_invalid_email_throws_exception(): void
    {
        $loginDTO = new LoginDTO('nonexistent@example.com', 'password123');

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->loginAction->execute($loginDTO);
    }

    public function test_execute_with_invalid_password_throws_exception(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $loginDTO = new LoginDTO('test@example.com', 'wrongpassword');

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->loginAction->execute($loginDTO);
    }
}
