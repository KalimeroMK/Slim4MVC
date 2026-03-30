<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

class CheckRoleMiddleware
{
    public function __invoke(ServerRequestInterface $serverRequest, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $user = $serverRequest->getAttribute('user');
        $route = RouteContext::fromRequest($serverRequest)->getRoute();
        // @phpstan-ignore-next-line
        $roles = $route?->getArgument('roles');

        // Convert single role to array
        // @phpstan-ignore-next-line
        if (! is_array($roles)) {
            $roles = [$roles];
        }

        $hasRole = array_any($roles, fn ($role): bool => $user && $user->hasRole($role));

        if (! $hasRole) {
            $response = new Response();
            $json = json_encode([
                'error' => 'Unauthorized',
                'message' => 'You do not have the required role to access this resource',
            ]);
            $response->getBody()->write($json !== false ? $json : '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        return $requestHandler->handle($serverRequest);
    }
}
