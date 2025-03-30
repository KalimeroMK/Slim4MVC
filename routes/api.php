<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthMiddleware;

return function ($app): void {
    // Authentication routes
    $app->post('/api/v1/register', [AuthController::class, 'register']);
    $app->post('/api/v1/login', [AuthController::class, 'login']);

    // User routes (protected by AuthMiddleware)
    $app->group('/api/v1', function ($app): void {
        $app->get('/users', [UserController::class, 'index']);
        $app->put('/user/{id}', [UserController::class, 'update']);
        $app->delete('/user/{id}', [UserController::class, 'destroy']);
    })->add(new AuthMiddleware);
};
