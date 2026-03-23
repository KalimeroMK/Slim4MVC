<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Controllers\Admin\DashboardController;
use App\Modules\Core\Infrastructure\Http\Controllers\Admin\PermissionController;
use App\Modules\Core\Infrastructure\Http\Controllers\Admin\RoleController;
use App\Modules\Core\Infrastructure\Http\Controllers\Admin\UserController;
use App\Modules\Core\Infrastructure\Http\Controllers\Web\HomeController;
use App\Modules\Core\Infrastructure\Http\Middleware\AuthWebMiddleware;
use App\Modules\Core\Infrastructure\Support\Route;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    Route::add('home', '/');

    // Dashboard route
    $app->get('/dashboard', [DashboardController::class, 'dashboard'])->add(AuthWebMiddleware::class);
    Route::add('dashboard', '/dashboard');

    // Admin Routes (require authentication)
    $app->group('/admin', function ($group) {
        // Roles Management
        $group->get('/roles', [RoleController::class, 'index']);
        $group->get('/roles/create', [RoleController::class, 'create']);
        $group->post('/roles', [RoleController::class, 'store']);
        $group->get('/roles/{id}/edit', [RoleController::class, 'edit']);
        $group->put('/roles/{id}', [RoleController::class, 'update']);
        $group->delete('/roles/{id}/delete', [RoleController::class, 'delete']);

        // Permissions Management
        $group->get('/permissions', [PermissionController::class, 'index']);
        $group->get('/permissions/create', [PermissionController::class, 'create']);
        $group->post('/permissions', [PermissionController::class, 'store']);
        $group->get('/permissions/{id}/edit', [PermissionController::class, 'edit']);
        $group->put('/permissions/{id}', [PermissionController::class, 'update']);
        $group->delete('/permissions/{id}/delete', [PermissionController::class, 'delete']);

        // Users Management
        $group->get('/users', [UserController::class, 'index']);
        $group->get('/users/create', [UserController::class, 'create']);
        $group->post('/users', [UserController::class, 'store']);
        $group->get('/users/{id}/edit', [UserController::class, 'edit']);
        $group->put('/users/{id}', [UserController::class, 'update']);
        $group->put('/users/{id}/password', [UserController::class, 'updatePassword']);
        $group->delete('/users/{id}/delete', [UserController::class, 'delete']);
    })->add(AuthWebMiddleware::class);

    // API Documentation (Swagger UI)
    $app->get('/api-docs', function (Request $request, Response $response) {
        return view('swagger', $response);
    });
    Route::add('api.docs', '/api-docs');
};
