<?php

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

class CheckRoleMiddleware
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $route = RouteContext::fromRequest($request)->getRoute();
        $requiredRole = $route->getArgument('role');

        if (!$user || !$user->hasRole($requiredRole)) {
            $response = new Response();
            $response->getBody()->write('Unauthorized');
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }
}