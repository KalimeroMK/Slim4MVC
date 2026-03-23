<?php

declare(strict_types=1);

use App\Modules\Auth\Infrastructure\Http\Controllers\Web\AuthController;
use App\Modules\Core\Infrastructure\Support\Route;
use Slim\App;

return function (App $app): void {
    // Authentication Routes
    $app->get('/register', [AuthController::class, 'showRegisterForm']);
    Route::add('register', '/register');

    $app->post('/register', [AuthController::class, 'register']);
    Route::add('register.post', '/register');

    $app->get('/login', [AuthController::class, 'showLoginForm']);
    Route::add('login', '/login');

    $app->post('/login', [AuthController::class, 'login']);
    Route::add('login.post', '/login');

    $app->post('/logout', [AuthController::class, 'logout']);
    Route::add('logout', '/logout');

    // Password Reset Routes
    $app->get('/forgot-password', [AuthController::class, 'showPasswordResetForm']);
    Route::add('password.request', '/forgot-password');

    $app->post('/forgot-password', [AuthController::class, 'sendPasswordResetLink']);
    Route::add('password.email', '/forgot-password');

    $app->get('/reset-password/{token}', [AuthController::class, 'showPasswordUpdateForm']);
    Route::add('password.reset', '/reset-password/{token}');

    $app->post('/reset-password', [AuthController::class, 'updatePassword']);
    Route::add('password.update', '/reset-password');
};
