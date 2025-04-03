<?php

declare(strict_types=1);

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AuthWebMiddleware;

return function ($app, $container): void {

    // Add middleware
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware(true, true, true);

    // Register other middlewares
    $container->set(AuthMiddleware::class, function () use ($container): AuthMiddleware {
        return new AuthMiddleware($container->get(App\Support\Auth::class));
    });

    $container->set(AuthWebMiddleware::class, function () use ($container): AuthWebMiddleware {
        return new AuthWebMiddleware($container->get(App\Support\Auth::class));
    });
};
