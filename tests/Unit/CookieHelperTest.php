<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Support\CookieHelper;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CookieHelperTest extends TestCase
{
    private CookieHelper $cookieHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_KEY'] = 'test-secret-key-32-chars-long!!';
        $_ENV['COOKIE_ENCRYPT'] = 'false';
        $this->cookieHelper = new CookieHelper(encryptionEnabled: false);
    }

    protected function tearDown(): void
    {
        // Clean up test cookies
        foreach (array_keys($_COOKIE) as $name) {
            unset($_COOKIE[$name]);
        }

        parent::tearDown();
    }

    public function test_singleton_instance(): void
    {
        $cookieHelper = CookieHelper::getInstance();
        $instance2 = CookieHelper::getInstance();

        $this->assertSame($cookieHelper, $instance2);
    }

    public function test_set_and_get_cookie(): void
    {
        $this->cookieHelper->set('test_key', 'test_value', 3600);

        $this->assertEquals('test_value', $this->cookieHelper->get('test_key'));
    }

    public function test_get_returns_default_for_missing_cookie(): void
    {
        $this->assertNull($this->cookieHelper->get('nonexistent'));
        $this->assertEquals('default', $this->cookieHelper->get('nonexistent', 'default'));
    }

    public function test_has_cookie(): void
    {
        $this->assertFalse($this->cookieHelper->has('test_key'));

        $this->cookieHelper->set('test_key', 'value');

        $this->assertTrue($this->cookieHelper->has('test_key'));
    }

    public function test_delete_cookie(): void
    {
        $this->cookieHelper->set('test_key', 'value');
        $this->assertTrue($this->cookieHelper->has('test_key'));

        $this->cookieHelper->delete('test_key');

        $this->assertFalse($this->cookieHelper->has('test_key'));
        $this->assertNull($this->cookieHelper->get('test_key'));
    }

    public function test_delete_multiple_cookies(): void
    {
        $this->cookieHelper->set('key1', 'value1');
        $this->cookieHelper->set('key2', 'value2');
        $this->cookieHelper->set('key3', 'value3');

        $this->cookieHelper->deleteMultiple(['key1', 'key2']);

        $this->assertFalse($this->cookieHelper->has('key1'));
        $this->assertFalse($this->cookieHelper->has('key2'));
        $this->assertTrue($this->cookieHelper->has('key3'));
    }

    public function test_forever_cookie(): void
    {
        $this->cookieHelper->forever('remember_me', 'user123');

        $this->assertEquals('user123', $this->cookieHelper->get('remember_me'));
        $this->assertTrue($this->cookieHelper->has('remember_me'));
    }

    public function test_get_all_cookies(): void
    {
        $this->cookieHelper->set('key1', 'value1');
        $this->cookieHelper->set('key2', 'value2');

        $all = $this->cookieHelper->all();

        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
        $this->assertEquals('value1', $all['key1']);
        $this->assertEquals('value2', $all['key2']);
    }

    public function test_make_cookie_for_slim_response(): void
    {
        $cookie = $this->cookieHelper->make('test', 'value', 3600);

        $this->assertArrayHasKey('value', $cookie);
        $this->assertArrayHasKey('expires', $cookie);
        $this->assertArrayHasKey('path', $cookie);
        $this->assertArrayHasKey('domain', $cookie);
        $this->assertArrayHasKey('secure', $cookie);
        $this->assertArrayHasKey('httponly', $cookie);
        $this->assertArrayHasKey('samesite', $cookie);

        $this->assertGreaterThan(time(), $cookie['expires']);
        $this->assertEquals('/', $cookie['path']);
        $this->assertEquals('Lax', $cookie['samesite']);
    }

    public function test_store_array_value(): void
    {
        $data = ['user_id' => 123, 'role' => 'admin'];
        $this->cookieHelper->set('user_data', $data);

        $retrieved = $this->cookieHelper->get('user_data');

        $this->assertEquals($data, $retrieved);
    }

    public function test_store_object_value(): void
    {
        $obj = new stdClass();
        $obj->name = 'Test';
        $obj->value = 123;

        $this->cookieHelper->set('test_obj', $obj);
        $retrieved = $this->cookieHelper->get('test_obj');

        $this->assertEquals($obj, $retrieved);
    }

    public function test_cookie_helper_function(): void
    {
        $_ENV['COOKIE_ENCRYPT'] = 'false';
        cookie_set('helper_test', 'value123');

        $this->assertEquals('value123', cookie_get('helper_test'));
        $this->assertTrue(cookie_has('helper_test'));
    }

    public function test_cookie_remember(): void
    {
        $_ENV['COOKIE_ENCRYPT'] = 'false';
        $callCount = 0;

        $result1 = cookie_remember('remember_test', 3600, function () use (&$callCount): string {
            $callCount++;

            return 'computed_value';
        });

        $this->assertSame(1, $callCount);
        $this->assertEquals('computed_value', $result1);

        // Second call should use cached value
        $result2 = cookie_remember('remember_test', 3600, function () use (&$callCount): string {
            $callCount++;

            return 'new_value';
        });

        $this->assertSame(1, $callCount);
        $this->assertEquals('computed_value', $result2);
    }

    public function test_encryption_enabled(): void
    {
        $cookieHelper = new CookieHelper(
            secret: 'test-secret-key-32-chars-long!!',
            encryptionEnabled: true
        );

        $this->assertTrue($cookieHelper->isEncryptionEnabled());

        // Test encryption via make() which doesn't use setcookie
        $cookie = $cookieHelper->make('secret', 'my-secret-data', 3600);

        // Set the cookie manually in $_COOKIE
        $_COOKIE['secret'] = $cookie['value'];

        $value = $cookieHelper->get('secret');

        $this->assertEquals('my-secret-data', $value);
    }

    public function test_encryption_disable_enable(): void
    {
        $cookieHelper = new CookieHelper(secret: 'test-key', encryptionEnabled: true);

        $this->assertTrue($cookieHelper->isEncryptionEnabled());

        $cookieHelper->disableEncryption();
        $this->assertFalse($cookieHelper->isEncryptionEnabled());

        $cookieHelper->enableEncryption();
        $this->assertTrue($cookieHelper->isEncryptionEnabled());
    }

    public function test_custom_cookie_options(): void
    {
        $cookieHelper = new CookieHelper(
            defaultPath: '/admin',
            defaultSecure: true,
            defaultHttpOnly: true,
            sameSite: 'Strict'
        );

        $cookie = $cookieHelper->make('test', 'value');

        $this->assertEquals('/admin', $cookie['path']);
        $this->assertTrue($cookie['secure']);
        $this->assertTrue($cookie['httponly']);
        $this->assertEquals('Strict', $cookie['samesite']);
    }

    public function test_session_cookie_no_expires(): void
    {
        $cookie = $this->cookieHelper->make('session', 'value', null);

        $this->assertEquals(0, $cookie['expires']);
    }
}
