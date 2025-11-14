<?php

declare(strict_types=1);

use App\Modules\Auth\Infrastructure\Http\Controllers\Web\AuthController;
use Slim\App;

return function (App $app): void {
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
};
