<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Providers;

use DI\Container;
use Psr\Container\ContainerInterface;
use Slim\App;

class AuthServiceProvider
{
    /**
     * Register module services in the container.
     */
    public function register(ContainerInterface $container): void
    {
        // Auth module doesn't have repositories to register
        // Actions are automatically resolved by PHP-DI autowiring
    }

    /**
     * Boot module routes and any other boot-time logic.
     */
    public function boot(App $app): void
    {
        // Load API routes
        $apiRoutesPath = __DIR__.'/../Routes/api.php';
        if (file_exists($apiRoutesPath)) {
            (require $apiRoutesPath)($app);
        }

        // Load Web routes
        $webRoutesPath = __DIR__.'/../Routes/web.php';
        if (file_exists($webRoutesPath)) {
            (require $webRoutesPath)($app);
        }
    }
}
