<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\Permission\Infrastructure\Http\Controllers\PermissionController;
use Psr\Container\ContainerInterface;
use Slim\App;

return function (App $app): void {
    $container = $app->getContainer();

    if (! $container instanceof ContainerInterface) {
        return;
    }

    $auth = $container->get(Auth::class);
    $authMiddleware = new AuthMiddleware($auth);

    $app->group('/api/v1/permissions', function ($group): void {
        $group->get('', [PermissionController::class, 'index']);
        $group->post('', [PermissionController::class, 'store']);
        $group->get('/{id}', [PermissionController::class, 'show']);
        $group->put('/{id}', [PermissionController::class, 'update']);
        $group->patch('/{id}', [PermissionController::class, 'update']);
        $group->delete('/{id}', [PermissionController::class, 'destroy']);

        // Additional permission-specific routes
        $group->get('/{id}/roles', [PermissionController::class, 'roles']);
    })->add($authMiddleware);
};
