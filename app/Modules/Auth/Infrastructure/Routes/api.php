<?php

declare(strict_types=1);

use App\Modules\Auth\Infrastructure\Http\Controllers\Api\AuthController;
use App\Modules\Core\Infrastructure\Http\Middleware\RateLimitMiddleware;
use Slim\App;

return function (App $app): void {
    // Rate limiting for auth endpoints (stricter: 5 requests per minute)
    $authRateLimit = new RateLimitMiddleware(5, 60);

    // Authentication routes with rate limiting
    $app->post('/api/v1/register', [AuthController::class, 'register'])->add($authRateLimit);
    $app->post('/api/v1/login', [AuthController::class, 'login'])->add($authRateLimit);
    $app->post('/api/v1/password-recovery', [AuthController::class, 'passwordRecovery'])->add($authRateLimit);
    $app->post('/api/v1/reset-password', [AuthController::class, 'updatePassword'])->add($authRateLimit);
};
