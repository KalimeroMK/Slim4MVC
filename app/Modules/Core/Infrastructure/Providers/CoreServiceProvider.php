<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Providers;

use DI\Container;
use Psr\Container\ContainerInterface;
use Slim\App;

class CoreServiceProvider
{
    /**
     * Register module services in the container.
     */
    public function register(ContainerInterface $container): void
    {
        // Core module doesn't have repositories to register
        // Base classes are available globally via autoloading

        // Actions are automatically resolved by PHP-DI autowiring
        // No need to manually register them
    }

    /**
     * Boot module routes and any other boot-time logic.
     */
    public function boot(App $app): void
    {
        // Core module doesn't have routes anymore
        // Auth routes are in Auth module
    }
}
