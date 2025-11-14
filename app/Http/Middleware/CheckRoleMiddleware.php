<?php

declare(strict_types=1);

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
        $roles = $route->getArgument('roles');

        // Convert single role to array
        if (! is_array($roles)) {
            $roles = [$roles];
        }

        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user && $user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (! $hasRole) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'You do not have the required role to access this resource',
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        return $handler->handle($request);
    }
}
