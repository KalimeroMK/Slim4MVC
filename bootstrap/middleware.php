<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\AuthWebMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ClearFlashDataMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\CsrfMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ExceptionHandlerMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\SecurityHeadersMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\ValidationExceptionMiddleware;
use App\Modules\Core\Infrastructure\Support\RequestResolver;
use Illuminate\Validation\Factory;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Modules\Core\Infrastructure\Http\Middleware\CorsMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;

return function ($app, DI\Container $container): void {

    // Configure logging
    $logger = new Logger('app');
    $logLevel = ($_ENV['APP_ENV'] ?? 'production') === 'production' ? Level::Warning : Level::Debug;
    $logger->pushHandler(new StreamHandler(__DIR__.'/../storage/logs/slim.log', $logLevel));
    $container->set(LoggerInterface::class, $logger);

    // Set container for Logger helper class
    App\Modules\Core\Infrastructure\Support\Logger::setContainer($container);

    // Add CORS middleware for API routes (using PSR-17 ResponseFactory)
    $cors = new CorsMiddleware(
        $container->get(ResponseFactoryInterface::class),
        [
            'origin' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'headers.allow' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'headers.expose' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
            'credentials' => true,
            'cache' => 86400,
        ]
    );

    // Add middleware
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    // Add CSRF middleware (generates token for web routes, validates non-GET requests)
    $app->add(new CsrfMiddleware());

    // Ensure body parsing works for form data
    $app->add(function ($request, $handler) {
        $contentType = $request->getHeaderLine('Content-Type');
        if (mb_strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $parsedBody = $request->getParsedBody();
            if ($parsedBody === null) {
                $body = (string) $request->getBody();
                parse_str($body, $data);
                $request = $request->withParsedBody($data);
            }
        }

        return $handler->handle($request);
    });

    // Add CORS middleware (should be early in the stack)
    $app->add($cors);

    // Only show error details in non-production environments
    $displayErrorDetails = ($_ENV['APP_ENV'] ?? 'production') !== 'production';
    $app->addErrorMiddleware($displayErrorDetails, true, true);

    // Register request resolver
    $container->set(RequestResolver::class, fn (): RequestResolver => new RequestResolver(
        $container->get(Factory::class)
    ));

    // Register other middlewares
    $container->set(AuthMiddleware::class, fn (): AuthMiddleware => new AuthMiddleware($container->get(App\Modules\Core\Infrastructure\Support\Auth::class)));

    $container->set(AuthWebMiddleware::class, fn (): AuthWebMiddleware => new AuthWebMiddleware($container->get(App\Modules\Core\Infrastructure\Support\Auth::class)));

    // Add security headers to all responses
    $app->add(new SecurityHeadersMiddleware());

    // Add flash data clearing (outermost - executes last)
    $app->add(new ClearFlashDataMiddleware());

    // Add validation exception handling
    $app->add(new ValidationExceptionMiddleware());

    // Add exception handler middleware (innermost - executes first to catch all)
    $app->add(new ExceptionHandlerMiddleware());
};
