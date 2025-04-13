<?php

declare(strict_types=1);

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AuthWebMiddleware;
use App\Http\Middleware\ClearFlashDataMiddleware;
use App\Http\Middleware\ValidationExceptionMiddleware;
use App\Support\RequestResolver;
use Illuminate\Validation\Factory;

return function ($app, $container): void {

    // Add middleware
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware(true, true, true);

    // Register request resolver
    $container->set(RequestResolver::class, function () use ($container): RequestResolver {
        return new RequestResolver(
            $container,
            $container->get(Factory::class)
        );
    });

    // Register other middlewares
    $container->set(AuthMiddleware::class, function () use ($container): AuthMiddleware {
        return new AuthMiddleware($container->get(App\Support\Auth::class));
    });

    $container->set(AuthWebMiddleware::class, function () use ($container): AuthWebMiddleware {
        return new AuthWebMiddleware($container->get(App\Support\Auth::class));
    });

    // Add validation exception handling
    $app->add(new ValidationExceptionMiddleware());

    // Add flash data clearing
    $app->add(new ClearFlashDataMiddleware());
};
