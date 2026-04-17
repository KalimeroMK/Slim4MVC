<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use App\Modules\Core\Infrastructure\View\Blade;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \App\Modules\Core\Infrastructure\View\Blade
 */
final class BladeSecurityTest extends TestCase
{
    private string $viewsPath;

    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewsPath = __DIR__.'/../../../resources/views';
        $this->cachePath = __DIR__.'/../../../storage/cache/views';
    }

    public function test_does_not_expose_sensitive_session_keys(): void
    {
        $_SESSION = [
            'user_id' => 1,
            'user_name' => 'Test',
            'password' => 'secret123',
            'csrf_token' => 'abc123',
            'secret' => 'hidden',
            'token' => 'jwt-token',
            'jwt' => 'another-token',
            'user_password' => 'mypass',
        ];

        $blade = new Blade($this->viewsPath, $this->cachePath);
        $shared = $this->getSharedData($blade, '_session');

        $this->assertArrayHasKey('user_id', $shared);
        $this->assertArrayHasKey('user_name', $shared);
        $this->assertArrayNotHasKey('password', $shared);
        $this->assertArrayNotHasKey('csrf_token', $shared);
        $this->assertArrayNotHasKey('secret', $shared);
        $this->assertArrayNotHasKey('token', $shared);
        $this->assertArrayNotHasKey('jwt', $shared);
        $this->assertArrayNotHasKey('user_password', $shared);
    }

    public function test_escapes_error_messages(): void
    {
        $_SESSION = [
            'errors' => [
                'email' => '<script>alert("xss")</script>',
                'name' => 'Normal error',
            ],
        ];

        $blade = new Blade($this->viewsPath, $this->cachePath);
        $errors = $this->getSharedData($blade, 'errors');

        $this->assertStringNotContainsString('<script>', $errors['email']);
        $this->assertStringContainsString('&lt;script&gt;', $errors['email']);
        $this->assertSame('Normal error', $errors['name']);
    }

    public function test_escapes_old_input(): void
    {
        $_SESSION = [
            'old' => [
                'comment' => '<img src=x onerror=alert(1)>',
            ],
        ];

        $blade = new Blade($this->viewsPath, $this->cachePath);
        $old = $this->getSharedData($blade, 'old');

        $this->assertStringNotContainsString('<img', $old['comment']);
        $this->assertStringContainsString('&lt;img', $old['comment']);
    }

    public function test_escapes_csrf_token(): void
    {
        $_SESSION = [
            'csrf_token' => '<script>bad</script>',
        ];

        $blade = new Blade($this->viewsPath, $this->cachePath);
        $token = $this->getSharedData($blade, '_token');

        $this->assertStringNotContainsString('<script>', $token);
        $this->assertStringContainsString('&lt;script&gt;', $token);
    }

    public function test_preserves_non_string_values_in_session(): void
    {
        $_SESSION = [
            'user_id' => 42,
            'is_admin' => false,
            'count' => 0,
        ];

        $blade = new Blade($this->viewsPath, $this->cachePath);
        $session = $this->getSharedData($blade, '_session');

        $this->assertSame(42, $session['user_id']);
        $this->assertFalse($session['is_admin']);
        $this->assertSame(0, $session['count']);
    }

    public function test_escapes_nested_array_values(): void
    {
        $_SESSION = [
            'errors' => [
                'nested' => [
                    'field' => '<b>bold</b>',
                ],
            ],
        ];

        $blade = new Blade($this->viewsPath, $this->cachePath);
        $errors = $this->getSharedData($blade, 'errors');

        $this->assertStringContainsString('&lt;b&gt;', $errors['nested']['field']);
    }

    private function getSharedData(Blade $blade, string $key): mixed
    {
        $reflection = new ReflectionClass($blade);
        $property = $reflection->getProperty('sharedData');
        $property->setAccessible(true);
        $data = $property->getValue($blade);

        return $data[$key] ?? null;
    }
}
