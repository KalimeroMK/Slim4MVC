<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\LoginAction;
use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Support\AdvancedJwtService;
use App\Modules\Core\Infrastructure\Support\Token\TokenPair;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Tests\TestCase;

final class LoginActionTest extends TestCase
{
    private LoginAction $loginAction;

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['JWT_SECRET'] = 'test-secret-key-for-testing-only-xx';
        $userRepository = new UserRepository();
        $jwtService = new AdvancedJwtService('test-secret-key-for-testing-only-xx');
        $this->loginAction = new LoginAction($userRepository, $jwtService);
    }

    public function test_execute_with_valid_credentials_returns_user_and_token_pair(): void
    {
        $user = User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $result = $this->loginAction->execute(new LoginDTO('test@example.com', 'password123'));

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token_pair', $result);
        $this->assertInstanceOf(TokenPair::class, $result['token_pair']);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token_pair']->getAccessToken());
        $this->assertNotEmpty($result['token_pair']->getRefreshToken());
    }

    public function test_execute_with_invalid_email_throws_exception(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->loginAction->execute(new LoginDTO('nonexistent@example.com', 'password123'));
    }

    public function test_execute_with_invalid_password_throws_exception(): void
    {
        User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->loginAction->execute(new LoginDTO('test@example.com', 'wrongpassword'));
    }
}
