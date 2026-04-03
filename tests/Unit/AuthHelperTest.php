<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Support\AuthHelper;
use PHPUnit\Framework\TestCase;

final class AuthHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AuthHelper::logout();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        AuthHelper::logout();
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_guest_returns_true_when_not_authenticated(): void
    {
        $this->assertTrue(AuthHelper::guest());
    }

    public function test_guest_returns_false_when_authenticated(): void
    {
        $_SESSION['user_id'] = 1;

        $this->assertFalse(AuthHelper::guest());
    }

    public function test_check_returns_false_when_no_user_in_session(): void
    {
        $this->assertFalse(AuthHelper::check());
    }

    public function test_check_returns_true_when_user_in_session(): void
    {
        $_SESSION['user_id'] = 1;

        $this->assertTrue(AuthHelper::check());
    }

    public function test_user_returns_null_when_not_authenticated(): void
    {
        $this->assertNull(AuthHelper::user());
    }

    public function test_user_returns_user_data_when_authenticated(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Test User';
        $_SESSION['user_email'] = 'test@example.com';

        $user = AuthHelper::user();

        $this->assertEquals(1, $user['id']);
        $this->assertEquals('Test User', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
    }

    public function test_id_returns_null_when_not_authenticated(): void
    {
        $this->assertNull(AuthHelper::id());
    }

    public function test_id_returns_user_id_when_authenticated(): void
    {
        $_SESSION['user_id'] = 123;

        $this->assertSame(123, AuthHelper::id());
    }

    public function test_set_user_stores_user_data_in_session(): void
    {
        AuthHelper::setUser([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'roles' => ['admin'],
            'permissions' => ['create-user'],
        ]);

        $this->assertEquals(1, $_SESSION['user_id']);
        $this->assertEquals('Test User', $_SESSION['user_name']);
        $this->assertEquals('test@example.com', $_SESSION['user_email']);
        $this->assertEquals(['admin'], $_SESSION['user_roles']);
        $this->assertEquals(['create-user'], $_SESSION['user_permissions']);
    }

    public function test_logout_clears_user_data(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Test User';
        $_SESSION['user_email'] = 'test@example.com';

        AuthHelper::logout();

        $this->assertArrayNotHasKey('user_id', $_SESSION);
        $this->assertArrayNotHasKey('user_name', $_SESSION);
        $this->assertArrayNotHasKey('user_email', $_SESSION);
        $this->assertTrue(AuthHelper::guest());
    }

    public function test_has_role_returns_false_when_user_has_no_roles(): void
    {
        $_SESSION['user_roles'] = [];

        $this->assertFalse(AuthHelper::hasRole('admin'));
    }

    public function test_has_role_returns_true_when_user_has_role(): void
    {
        $_SESSION['user_roles'] = ['admin', 'user'];

        $this->assertTrue(AuthHelper::hasRole('admin'));
    }

    public function test_can_returns_false_when_user_has_no_permissions(): void
    {
        $_SESSION['user_permissions'] = [];

        $this->assertFalse(AuthHelper::can('create-user'));
    }

    public function test_can_returns_true_when_user_has_permission(): void
    {
        $_SESSION['user_permissions'] = ['create-user', 'delete-user'];

        $this->assertTrue(AuthHelper::can('create-user'));
    }
}
