<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

return function ($app) {
    // Add the api/v1 prefix to the routes
    $app->post('/api/v1/register', [AuthController::class, 'register']);
    $app->post('/api/v1/login', [AuthController::class, 'login']);

    $app->group('/api/v1', function ($app) {
        // The routes inside this group will have the /api/v1 prefix
        $app->put('/user/{id}', [UserController::class, 'update']);
    })->add(new AuthMiddleware);
};
