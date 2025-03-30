<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthMiddleware;

return function ($app): void {
    // Add the api/v1 prefix to the routes
    $app->post('/api/v1/register', [AuthController::class, 'register']);
    $app->post('/api/v1/login', [AuthController::class, 'login']);

    // Password recovery and reset routes
    $app->post('/api/v1/password-recovery', [AuthController::class, 'passwordRecovery']);
    $app->post('/api/v1/reset-password', [AuthController::class, 'resetPassword']);

    $app->group('/api/v1', function ($app): void {
        // The routes inside this group will have the /api/v1 prefix
        $app->put('/user/{id}', [UserController::class, 'update']);
    })->add(new AuthMiddleware);
};
