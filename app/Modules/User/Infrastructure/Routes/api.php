<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\User\Infrastructure\Http\Controllers\UserController;
use Psr\Container\ContainerInterface;
use Slim\App;

return function (App $app): void {
    $container = $app->getContainer();

    if (! $container instanceof ContainerInterface) {
        return;
    }

    $auth = $container->get(Auth::class);
    $authMiddleware = new AuthMiddleware($auth);

    $app->group('/api/v1/users', function ($group): void {
        $group->get('', [UserController::class, 'index']);
        $group->post('', [UserController::class, 'store']);
        $group->get('/{id}', [UserController::class, 'show']);
        $group->put('/{id}', [UserController::class, 'update']);
        $group->patch('/{id}', [UserController::class, 'update']);
        $group->delete('/{id}', [UserController::class, 'destroy']);
    })->add($authMiddleware);
};
