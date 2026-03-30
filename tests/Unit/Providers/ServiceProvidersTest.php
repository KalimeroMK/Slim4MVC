<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Modules\Auth\Infrastructure\Providers\AuthServiceProvider;
use App\Modules\Core\Infrastructure\Providers\CoreServiceProvider;
use App\Modules\Permission\Infrastructure\Providers\PermissionServiceProvider;
use App\Modules\Role\Infrastructure\Providers\RoleServiceProvider;
use App\Modules\User\Infrastructure\Providers\UserServiceProvider;
use PHPUnit\Framework\TestCase;

final class ServiceProvidersTest extends TestCase
{
    public function test_auth_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(AuthServiceProvider::class));
    }

    public function test_core_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(CoreServiceProvider::class));
    }

    public function test_permission_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(PermissionServiceProvider::class));
    }

    public function test_role_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(RoleServiceProvider::class));
    }

    public function test_user_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(UserServiceProvider::class));
    }

    public function test_service_providers_have_register_method(): void
    {
        $providers = [
            AuthServiceProvider::class,
            CoreServiceProvider::class,
            PermissionServiceProvider::class,
            RoleServiceProvider::class,
            UserServiceProvider::class,
        ];

        foreach ($providers as $provider) {
            $this->assertTrue(method_exists($provider, 'register'));
        }
    }

    public function test_service_providers_have_boot_method(): void
    {
        $providers = [
            AuthServiceProvider::class,
            CoreServiceProvider::class,
            PermissionServiceProvider::class,
            RoleServiceProvider::class,
            UserServiceProvider::class,
        ];

        foreach ($providers as $provider) {
            $this->assertTrue(method_exists($provider, 'boot'));
        }
    }
}
