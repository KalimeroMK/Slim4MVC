<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\LoginAction;
use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Support\JwtService;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use RuntimeException;
use Tests\TestCase;

class LoginActionTest extends TestCase
{
    private LoginAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new UserRepository();
        $jwtService = new JwtService('test-secret-key-for-testing-only');
        $this->action = new LoginAction($repository, $jwtService);
        $_ENV['JWT_SECRET'] = 'test-secret-key-for-testing-only';
    }

    public function test_execute_with_valid_credentials_returns_user_and_token(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $dto = new LoginDTO('test@example.com', 'password123');
        $result = $this->action->execute($dto);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertEquals($user->email, $result['user']->email);
        $this->assertNotEmpty($result['token']);
    }

    public function test_execute_with_invalid_email_throws_exception(): void
    {
        $dto = new LoginDTO('nonexistent@example.com', 'password123');

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->action->execute($dto);
    }

    public function test_execute_with_invalid_password_throws_exception(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $dto = new LoginDTO('test@example.com', 'wrongpassword');

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->action->execute($dto);
    }

    public function test_execute_without_jwt_secret_throws_exception(): void
    {
        unset($_ENV['JWT_SECRET']);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $dto = new LoginDTO('test@example.com', 'password123');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT_SECRET is not configured');

        $this->action->execute($dto);
    }
}
