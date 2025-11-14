<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Support\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = new Auth($this->container);
    }

    public function test_attempt_with_valid_credentials_returns_true(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $result = $this->auth->attempt('test@example.com', 'password123');

        $this->assertTrue($result);
    }

    public function test_attempt_with_invalid_email_returns_false(): void
    {
        $result = $this->auth->attempt('nonexistent@example.com', 'password123');

        $this->assertFalse($result);
    }

    public function test_attempt_with_invalid_password_returns_false(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $result = $this->auth->attempt('test@example.com', 'wrongpassword');

        $this->assertFalse($result);
    }

    public function test_check_returns_false_when_no_user_logged_in(): void
    {
        $result = $this->auth->check();

        $this->assertFalse($result);
    }

    public function test_logout_removes_user_from_session(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);

        $this->auth->attempt('test@example.com', 'password123');
        $this->auth->logout();

        $this->assertFalse($this->auth->check());
    }
}
