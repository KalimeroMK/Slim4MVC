<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\Auth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class AuthMiddleware implements MiddlewareInterface
{
    protected Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if (! $this->auth->check()) {
            return ApiResponse::unauthorized();
        }

        return $handler->handle($request);
    }
}
