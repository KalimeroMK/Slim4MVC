<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Controllers\Admin\DashboardController;
use App\Modules\Core\Infrastructure\Http\Controllers\Web\HomeController;
use App\Modules\Core\Infrastructure\Http\Middleware\AuthWebMiddleware;
use App\Modules\Core\Infrastructure\Support\Route;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    Route::add('home', '/');

    // Dashboard route (auth routes are loaded from Auth module)
    $app->get('/dashboard', [DashboardController::class, 'dashboard'])->add(AuthWebMiddleware::class);
    Route::add('dashboard', '/dashboard');

    // API Documentation (Swagger UI)
    $app->get('/api-docs', function (Request $request, Response $response) {
        return view('swagger', $response);
    });
    Route::add('api.docs', '/api-docs');
};
