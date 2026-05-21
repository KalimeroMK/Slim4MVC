<?php

declare(strict_types=1);

use App\Modules\Auth\Infrastructure\Http\Controllers\Api\AuthController;
use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Modules\Core\Infrastructure\Http\Middleware\RateLimitMiddleware;
use App\Modules\Core\Infrastructure\Support\Auth;
use Psr\Container\ContainerInterface;
use Slim\App;

return function (App $app): void {
    $container = $app->getContainer();

    // Stricter rate limiting for unauthenticated auth endpoints (5 req/min)
    $authRateLimit = $container instanceof ContainerInterface
        ? new RateLimitMiddleware($container->get(CacheInterface::class), 5, 60)
        : new RateLimitMiddleware(new \App\Modules\Core\Infrastructure\Cache\NullCache(), 5, 60);

    $authMiddleware = $container instanceof ContainerInterface
        ? new AuthMiddleware($container->get(Auth::class))
        : null;

    // Public auth routes
    $app->post('/api/v1/register', [AuthController::class, 'register'])->add($authRateLimit);
    $app->post('/api/v1/login', [AuthController::class, 'login'])->add($authRateLimit);
    $app->post('/api/v1/password-recovery', [AuthController::class, 'passwordRecovery'])->add($authRateLimit);
    $app->post('/api/v1/reset-password', [AuthController::class, 'updatePassword'])->add($authRateLimit);
    $app->post('/api/v1/refresh-token', [AuthController::class, 'refresh'])->add($authRateLimit);

    // Authenticated auth routes
    $logoutRoute = $app->post('/api/v1/logout', [AuthController::class, 'logout']);
    if ($authMiddleware !== null) {
        $logoutRoute->add($authMiddleware);
    }
};
