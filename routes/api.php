<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\AuthMiddleware;

return function ($app): void {
    // Authentication routes
    $app->post('/api/v1/register', [AuthController::class, 'register']);
    $app->post('/api/v1/login', [AuthController::class, 'login']);
    $app->post('/api/v1/password-recovery', [AuthController::class, 'passwordRecovery']);
    $app->post('/api/v1/reset-password', [AuthController::class, 'updatePassword']);

    $app->group('/api/v1/users', function ($group): void {
        $group->get('', [UserController::class, 'index']);
        $group->post('', [UserController::class, 'store']);
        $group->get('/{id}', [UserController::class, 'show']);
        $group->put('/{id}', [UserController::class, 'update']);
        $group->patch('/{id}', [UserController::class, 'update']);
        $group->delete('/{id}', [UserController::class, 'destroy']);
    })->add(AuthMiddleware::class);

    $app->group('/api/v1/roles', function ($group): void {
        $group->get('', [RoleController::class, 'index']);
        $group->post('', [RoleController::class, 'store']);
        $group->get('/{id}', [RoleController::class, 'show']);
        $group->put('/{id}', [RoleController::class, 'update']);
        $group->patch('/{id}', [RoleController::class, 'update']);
        $group->delete('/{id}', [RoleController::class, 'destroy']);
    })->add(AuthMiddleware::class);

    // Permission management routes
    $app->group('/api/v1/permissions', function ($group): void {
        $group->get('', [PermissionController::class, 'index']);
        $group->post('', [PermissionController::class, 'store']);
        $group->get('/{id}', [PermissionController::class, 'show']);
        $group->put('/{id}', [PermissionController::class, 'update']);
        $group->patch('/{id}', [PermissionController::class, 'update']);
        $group->delete('/{id}', [PermissionController::class, 'destroy']);

        // Additional permission-specific routes
        $group->get('/{id}/roles', [PermissionController::class, 'roles']);
    })->add(AuthMiddleware::class);
};
