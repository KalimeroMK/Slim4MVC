<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\App;

// Register and boot modules
return function (App $app, ContainerInterface $container): void {
    $modulesFile = __DIR__.'/modules-register.php';

    if (! file_exists($modulesFile)) {
        return; // No modules registered yet
    }

    $modules = require $modulesFile;

    if (! is_array($modules)) {
        return;
    }

    foreach ($modules as $providerClass) {
        if (is_string($providerClass) && class_exists($providerClass)) {
            try {
                $provider = new $providerClass();

                // Register services
                if (method_exists($provider, 'register')) {
                    $provider->register($container);
                }

                // Boot routes
                if (method_exists($provider, 'boot')) {
                    $provider->boot($app);
                }
            } catch (Throwable $e) {
                // Log error but don't break the application
                error_log("Failed to load module $providerClass: ".$e->getMessage());
            }
        }
    }
};
