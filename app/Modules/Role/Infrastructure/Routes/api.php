<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\Role\Infrastructure\Http\Controllers\RoleController;
use Psr\Container\ContainerInterface;
use Slim\App;

return function (App $app): void {
    $container = $app->getContainer();

    if (! $container instanceof ContainerInterface) {
        return;
    }

    $auth = $container->get(Auth::class);
    $authMiddleware = new AuthMiddleware($auth);

    $app->group('/api/v1/roles', function ($group): void {
        $group->get('', [RoleController::class, 'index']);
        $group->post('', [RoleController::class, 'store']);
        $group->get('/{id}', [RoleController::class, 'show']);
        $group->put('/{id}', [RoleController::class, 'update']);
        $group->patch('/{id}', [RoleController::class, 'update']);
        $group->delete('/{id}', [RoleController::class, 'destroy']);
    })->add($authMiddleware);
};
