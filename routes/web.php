<?php

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

return function ($app) {
    $app->get('/', function ($request, $response) {
        $response->getBody()->write("Hello from Slim 4!");
        return $response;
    });

    $app->post('/register', [AuthController::class, 'register']);
    $app->post('/login', [AuthController::class, 'login']);

    $app->group('', function ($app) {
        $app->put('/user/{id}', [UserController::class, 'update']);
    })->add(new AuthMiddleware());
};
