<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\CsrfMiddleware;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/register', [AuthController::class, 'show'])->add(CsrfMiddleware::class);

};
