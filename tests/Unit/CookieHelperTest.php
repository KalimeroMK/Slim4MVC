<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Core\Infrastructure\Support\CookieHelper;
use PHPUnit\Framework\TestCase;

final class CookieHelperTest extends TestCase
{
    private CookieHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_KEY'] = 'test-secret-key-32-chars-long!!';
        $_ENV['COOKIE_ENCRYPT'] = 'false';
        $this->helper = new CookieHelper(encryptionEnabled: false);
    }

    protected function tearDown(): void
    {
        // Clean up test cookies
        foreach ($_COOKIE as $name => $value) {
            unset($_COOKIE[$name]);
        }
        parent::tearDown();
    }

    public function test_singleton_instance(): void
    {
        $instance1 = CookieHelper::getInstance();
        $instance2 = CookieHelper::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function test_set_and_get_cookie(): void
    {
        $this->helper->set('test_key', 'test_value', 3600);

        $this->assertEquals('test_value', $this->helper->get('test_key'));
    }

    public function test_get_returns_default_for_missing_cookie(): void
    {
        $this->assertNull($this->helper->get('nonexistent'));
        $this->assertEquals('default', $this->helper->get('nonexistent', 'default'));
    }

    public function test_has_cookie(): void
    {
        $this->assertFalse($this->helper->has('test_key'));

        $this->helper->set('test_key', 'value');

        $this->assertTrue($this->helper->has('test_key'));
    }

    public function test_delete_cookie(): void
    {
        $this->helper->set('test_key', 'value');
        $this->assertTrue($this->helper->has('test_key'));

        $this->helper->delete('test_key');

        $this->assertFalse($this->helper->has('test_key'));
        $this->assertNull($this->helper->get('test_key'));
    }

    public function test_delete_multiple_cookies(): void
    {
        $this->helper->set('key1', 'value1');
        $this->helper->set('key2', 'value2');
        $this->helper->set('key3', 'value3');

        $this->helper->deleteMultiple(['key1', 'key2']);

        $this->assertFalse($this->helper->has('key1'));
        $this->assertFalse($this->helper->has('key2'));
        $this->assertTrue($this->helper->has('key3'));
    }

    public function test_forever_cookie(): void
    {
        $this->helper->forever('remember_me', 'user123');

        $this->assertEquals('user123', $this->helper->get('remember_me'));
        $this->assertTrue($this->helper->has('remember_me'));
    }

    public function test_get_all_cookies(): void
    {
        $this->helper->set('key1', 'value1');
        $this->helper->set('key2', 'value2');

        $all = $this->helper->all();

        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
        $this->assertEquals('value1', $all['key1']);
        $this->assertEquals('value2', $all['key2']);
    }

    public function test_make_cookie_for_slim_response(): void
    {
        $cookie = $this->helper->make('test', 'value', 3600);

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
        $this->helper->set('user_data', $data);

        $retrieved = $this->helper->get('user_data');

        $this->assertEquals($data, $retrieved);
    }

    public function test_store_object_value(): void
    {
        $obj = new \stdClass();
        $obj->name = 'Test';
        $obj->value = 123;

        $this->helper->set('test_obj', $obj);
        $retrieved = $this->helper->get('test_obj');

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

        $result1 = cookie_remember('remember_test', 3600, function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        });

        $this->assertEquals(1, $callCount);
        $this->assertEquals('computed_value', $result1);

        // Second call should use cached value
        $result2 = cookie_remember('remember_test', 3600, function () use (&$callCount) {
            $callCount++;
            return 'new_value';
        });

        $this->assertEquals(1, $callCount);
        $this->assertEquals('computed_value', $result2);
    }

    public function test_encryption_enabled(): void
    {
        $encryptedHelper = new CookieHelper(
            secret: 'test-secret-key-32-chars-long!!',
            encryptionEnabled: true
        );

        $this->assertTrue($encryptedHelper->isEncryptionEnabled());

        // Test encryption via make() which doesn't use setcookie
        $cookie = $encryptedHelper->make('secret', 'my-secret-data', 3600);
        
        // Set the cookie manually in $_COOKIE
        $_COOKIE['secret'] = $cookie['value'];
        
        $value = $encryptedHelper->get('secret');

        $this->assertEquals('my-secret-data', $value);
    }

    public function test_encryption_disable_enable(): void
    {
        $helper = new CookieHelper(secret: 'test-key', encryptionEnabled: true);

        $this->assertTrue($helper->isEncryptionEnabled());

        $helper->disableEncryption();
        $this->assertFalse($helper->isEncryptionEnabled());

        $helper->enableEncryption();
        $this->assertTrue($helper->isEncryptionEnabled());
    }

    public function test_custom_cookie_options(): void
    {
        $helper = new CookieHelper(
            defaultPath: '/admin',
            defaultSecure: true,
            defaultHttpOnly: true,
            sameSite: 'Strict'
        );

        $cookie = $helper->make('test', 'value');

        $this->assertEquals('/admin', $cookie['path']);
        $this->assertTrue($cookie['secure']);
        $this->assertTrue($cookie['httponly']);
        $this->assertEquals('Strict', $cookie['samesite']);
    }

    public function test_session_cookie_no_expires(): void
    {
        $cookie = $this->helper->make('session', 'value', null);

        $this->assertEquals(0, $cookie['expires']);
    }
}
