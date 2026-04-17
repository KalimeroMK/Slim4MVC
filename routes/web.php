<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Controllers\Admin\DashboardController;
use App\Modules\Core\Infrastructure\Http\Controllers\Web\HomeController;
use App\Modules\Core\Infrastructure\Http\Middleware\AuthWebMiddleware;
use App\Modules\Core\Infrastructure\Support\Route;
use App\Modules\Permission\Infrastructure\Http\Controllers\Web\PermissionController;
use App\Modules\Role\Infrastructure\Http\Controllers\Web\RoleController;
use App\Modules\User\Infrastructure\Http\Controllers\Web\UserController;
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
    $app->group('/admin', function ($group): void {
        // Roles Management
        $group->get('/roles', [RoleController::class, 'index']);
        Route::add('admin.roles.index', '/admin/roles');

        $group->get('/roles/create', [RoleController::class, 'create']);
        Route::add('admin.roles.create', '/admin/roles/create');

        $group->post('/roles', [RoleController::class, 'store']);
        Route::add('admin.roles.store', '/admin/roles');

        $group->get('/roles/{id}/edit', [RoleController::class, 'edit']);
        Route::add('admin.roles.edit', '/admin/roles/{id}/edit');

        $group->put('/roles/{id}', [RoleController::class, 'update']);
        Route::add('admin.roles.update', '/admin/roles/{id}');

        $group->delete('/roles/{id}/delete', [RoleController::class, 'delete']);
        Route::add('admin.roles.delete', '/admin/roles/{id}/delete');

        // Permissions Management
        $group->get('/permissions', [PermissionController::class, 'index']);
        Route::add('admin.permissions.index', '/admin/permissions');

        $group->get('/permissions/create', [PermissionController::class, 'create']);
        Route::add('admin.permissions.create', '/admin/permissions/create');

        $group->post('/permissions', [PermissionController::class, 'store']);
        Route::add('admin.permissions.store', '/admin/permissions');

        $group->get('/permissions/{id}/edit', [PermissionController::class, 'edit']);
        Route::add('admin.permissions.edit', '/admin/permissions/{id}/edit');

        $group->put('/permissions/{id}', [PermissionController::class, 'update']);
        Route::add('admin.permissions.update', '/admin/permissions/{id}');

        $group->delete('/permissions/{id}/delete', [PermissionController::class, 'delete']);
        Route::add('admin.permissions.delete', '/admin/permissions/{id}/delete');

        // Users Management
        $group->get('/users', [UserController::class, 'index']);
        Route::add('admin.users.index', '/admin/users');

        $group->get('/users/create', [UserController::class, 'create']);
        Route::add('admin.users.create', '/admin/users/create');

        $group->post('/users', [UserController::class, 'store']);
        Route::add('admin.users.store', '/admin/users');

        $group->get('/users/{id}/edit', [UserController::class, 'edit']);
        Route::add('admin.users.edit', '/admin/users/{id}/edit');

        $group->put('/users/{id}', [UserController::class, 'update']);
        Route::add('admin.users.update', '/admin/users/{id}');

        $group->put('/users/{id}/password', [UserController::class, 'updatePassword']);
        Route::add('admin.users.password', '/admin/users/{id}/password');

        $group->delete('/users/{id}/delete', [UserController::class, 'delete']);
        Route::add('admin.users.delete', '/admin/users/{id}/delete');
    })->add(AuthWebMiddleware::class);

    // API Documentation (Swagger UI)
    $app->get('/api-docs', fn (Request $request, Response $response) => view('swagger', $response));
    Route::add('api.docs', '/api-docs');

    // Error/Success pages
    Route::add('login.error', '/login');
    Route::add('login.success', '/login');
};
