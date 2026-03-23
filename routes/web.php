<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Controllers\Admin\DashboardController;
use App\Modules\Core\Infrastructure\Http\Controllers\Web\HomeController;
use App\Modules\Core\Infrastructure\Http\Middleware\AuthWebMiddleware;
use App\Modules\Core\Infrastructure\Support\Route;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    Route::add('home', '/');

    // Dashboard route (auth routes are loaded from Auth module)
    $app->get('/dashboard', [DashboardController::class, 'dashboard'])->add(AuthWebMiddleware::class);
    Route::add('dashboard', '/dashboard');
};
