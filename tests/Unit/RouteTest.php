<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Support\Route;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::clear();
    }

    public function test_can_add_and_retrieve_route(): void
    {
        Route::add('login', '/login');

        $this->assertEquals('/login', Route::url('login'));
    }

    public function test_can_check_if_route_exists(): void
    {
        Route::add('login', '/login');

        $this->assertTrue(Route::has('login'));
        $this->assertFalse(Route::has('nonexistent'));
    }

    public function test_throws_exception_for_missing_route(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route [missing] not found.');

        Route::url('missing');
    }

    public function test_can_replace_single_parameter(): void
    {
        Route::add('user.show', '/users/{id}');

        $this->assertEquals('/users/123', Route::url('user.show', ['id' => 123]));
    }

    public function test_can_replace_multiple_parameters(): void
    {
        Route::add('user.posts.show', '/users/{userId}/posts/{postId}');

        $url = Route::url('user.posts.show', ['userId' => 1, 'postId' => 42]);

        $this->assertEquals('/users/1/posts/42', $url);
    }

    public function test_can_get_all_routes(): void
    {
        Route::add('login', '/login');
        Route::add('register', '/register');

        $routes = Route::all();

        $this->assertEquals(['login' => '/login', 'register' => '/register'], $routes);
    }

    public function test_clear_removes_all_routes(): void
    {
        Route::add('login', '/login');

        Route::clear();

        $this->assertFalse(Route::has('login'));
        $this->assertEquals([], Route::all());
    }
}
