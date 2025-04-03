<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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
            // Create response and set status and headers for JSON response
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));

            // Return the response with JSON header and status code
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        return $handler->handle($request);
    }
}
