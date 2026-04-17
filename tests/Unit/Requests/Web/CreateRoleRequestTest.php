<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Web;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;
use App\Modules\Role\Infrastructure\Http\Requests\Web\CreateRoleRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \App\Modules\Role\Infrastructure\Http\Requests\Web\CreateRoleRequest
 */
final class CreateRoleRequestTest extends TestCase
{
    public function test_create_role_request_extends_form_request(): void
    {
        $this->assertTrue(is_subclass_of(CreateRoleRequest::class, FormRequest::class));
    }

    public function test_create_role_request_exists(): void
    {
        $this->assertTrue(class_exists(CreateRoleRequest::class));
    }

    public function test_request_has_rules_method(): void
    {
        $reflection = new ReflectionClass(CreateRoleRequest::class);
        $this->assertTrue($reflection->hasMethod('rules'));

        $method = $reflection->getMethod('rules');
        $this->assertTrue($method->isProtected());
    }

    public function test_request_has_messages_method(): void
    {
        $reflection = new ReflectionClass(CreateRoleRequest::class);
        $this->assertTrue($reflection->hasMethod('messages'));

        $method = $reflection->getMethod('messages');
        $this->assertTrue($method->isProtected());
    }

    public function test_rules_returns_expected_structure_via_reflection(): void
    {
        $reflection = new ReflectionClass(CreateRoleRequest::class);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);

        // Create a minimal instance with mocked dependencies
        $request = $reflection->newInstanceWithoutConstructor();
        $rules = $method->invoke($request);

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('permissions', $rules);
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('string', $rules['name']);
        $this->assertStringContainsString('max:20', $rules['name']);
        $this->assertStringContainsString('nullable', $rules['permissions']);
        $this->assertStringContainsString('array', $rules['permissions']);
    }

    public function test_messages_returns_custom_error_messages_via_reflection(): void
    {
        $reflection = new ReflectionClass(CreateRoleRequest::class);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);

        $request = $reflection->newInstanceWithoutConstructor();
        $messages = $method->invoke($request);

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.unique', $messages);
        $this->assertEquals('Role name is required', $messages['name.required']);
        $this->assertEquals('Role already exists', $messages['name.unique']);
    }
}
