<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Modules\Core\Infrastructure\Http\Middleware\AuthWebMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\CheckPermissionMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\CheckRoleMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\CsrfMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ExceptionHandlerMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ValidationExceptionMiddleware;
use PHPUnit\Framework\TestCase;

final class AdditionalMiddlewareTest extends TestCase
{
    public function test_auth_web_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(AuthWebMiddleware::class));
    }

    public function test_check_permission_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(CheckPermissionMiddleware::class));
    }

    public function test_check_role_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(CheckRoleMiddleware::class));
    }

    public function test_csrf_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(CsrfMiddleware::class));
    }

    public function test_exception_handler_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(ExceptionHandlerMiddleware::class));
    }

    public function test_validation_exception_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(ValidationExceptionMiddleware::class));
    }

    public function test_middlewares_have_invoke_or_process_method(): void
    {
        $middlewares = [
            AuthWebMiddleware::class,
            CheckPermissionMiddleware::class,
            CheckRoleMiddleware::class,
            CsrfMiddleware::class,
            ExceptionHandlerMiddleware::class,
            ValidationExceptionMiddleware::class,
        ];

        foreach ($middlewares as $middleware) {
            $hasMethod = method_exists($middleware, 'process') || method_exists($middleware, '__invoke');
            $this->assertTrue($hasMethod, $middleware . ' should have process or __invoke method');
        }
    }
}
