<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Providers;

use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;
use DI\Container;
use Psr\Container\ContainerInterface;
use Slim\App;

class PermissionServiceProvider
{
    /**
     * Register module services in the container.
     */
    public function register(ContainerInterface $container): void
    {
        // Register repositories
        if ($container instanceof Container) {
            $container->set(PermissionRepository::class, \DI\autowire(PermissionRepository::class));
        }

        // Actions are automatically resolved by PHP-DI autowiring
        // No need to manually register them
    }

    /**
     * Boot module routes and any other boot-time logic.
     */
    public function boot(App $app): void
    {
        $routesPath = __DIR__.'/../Routes/api.php';

        if (file_exists($routesPath)) {
            (require $routesPath)($app);
        }
    }
}
