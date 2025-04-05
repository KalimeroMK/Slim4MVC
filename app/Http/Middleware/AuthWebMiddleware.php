<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Auth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AuthWebMiddleware implements MiddlewareInterface
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if (! $this->auth->check()) {
            $response = new SlimResponse();

            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}
