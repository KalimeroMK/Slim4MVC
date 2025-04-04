<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->group('', function ($group): void {
        // Authentication Routes
        $group->get('/register', [AuthController::class, 'showRegisterForm']);
        $group->post('/register', [AuthController::class, 'register']);

        $group->get('/login', [AuthController::class, 'showLoginForm']);
        $group->post('/login', [AuthController::class, 'login']);

        $group->post('/logout', [AuthController::class, 'logout']);

        // Password Reset Routes
        $group->get('/forgot-password', [AuthController::class, 'showPasswordResetForm']);
        $group->post('/forgot-password', [AuthController::class, 'sendPasswordResetLink']);

        $group->get('/reset-password/{token}', [AuthController::class, 'showPasswordUpdateForm']);
        $group->post('/reset-password', [AuthController::class, 'updatePassword']);
    });
};
