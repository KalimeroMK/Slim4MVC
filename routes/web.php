<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AuthWebMiddleware;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    // Authentication Routes
    $app->get('/register', [AuthController::class, 'showRegisterForm']);
    $app->post('/register', [AuthController::class, 'register']);
    $app->get('/login', [AuthController::class, 'showLoginForm']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/logout', [AuthController::class, 'logout']);
    // Password Reset Routes
    $app->get('/forgot-password', [AuthController::class, 'showPasswordResetForm']);
    $app->post('/forgot-password', [AuthController::class, 'sendPasswordResetLink']);

    $app->get('/reset-password/{token}', [AuthController::class, 'showPasswordUpdateForm']);
    $app->post('/reset-password', [AuthController::class, 'updatePassword']);

    $app->get('/dashboard', [DashboardController::class, 'dashboard'])->add(AuthWebMiddleware::class);

};
