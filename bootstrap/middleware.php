<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\AuthWebMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ClearFlashDataMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ExceptionHandlerMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ValidationExceptionMiddleware;
use App\Modules\Core\Infrastructure\Support\RequestResolver;
use Illuminate\Validation\Factory;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Tuupola\Middleware\CorsMiddleware;

return function ($app, DI\Container $container): void {

    // Configure logging
    $logger = new Logger('app');
    $logLevel = ($_ENV['APP_ENV'] ?? 'production') === 'production' ? Level::Warning : Level::Debug;
    $logger->pushHandler(new StreamHandler(__DIR__.'/../storage/logs/slim.log', $logLevel));
    $container->set(LoggerInterface::class, $logger);

    // Set container for Logger helper class
    App\Modules\Core\Infrastructure\Support\Logger::setContainer($container);

    // Add CORS middleware for API routes
    $cors = new CorsMiddleware([
        'origin' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
        'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'headers.allow' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'headers.expose' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
        'credentials' => true,
        'cache' => 86400,
    ]);

    // Add middleware
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    // Add CORS middleware (should be early in the stack)
    $app->add($cors);

    // Only show error details in non-production environments
    $displayErrorDetails = ($_ENV['APP_ENV'] ?? 'production') !== 'production';
    $app->addErrorMiddleware($displayErrorDetails, true, true);

    // Register request resolver
    $container->set(RequestResolver::class, function () use ($container): RequestResolver {
        return new RequestResolver(
            $container,
            $container->get(Factory::class)
        );
    });

    // Register other middlewares
    $container->set(AuthMiddleware::class, function () use ($container): AuthMiddleware {
        return new AuthMiddleware($container->get(App\Modules\Core\Infrastructure\Support\Auth::class));
    });

    $container->set(AuthWebMiddleware::class, function () use ($container): AuthWebMiddleware {
        return new AuthWebMiddleware($container->get(App\Modules\Core\Infrastructure\Support\Auth::class));
    });

    // Add exception handler middleware (should be early to catch all exceptions)
    $app->add(new ExceptionHandlerMiddleware());

    // Add validation exception handling
    $app->add(new ValidationExceptionMiddleware());

    // Add flash data clearing
    $app->add(new ClearFlashDataMiddleware());
};
