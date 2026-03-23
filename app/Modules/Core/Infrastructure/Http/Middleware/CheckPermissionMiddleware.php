<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

class CheckPermissionMiddleware
{
    public function __invoke(ServerRequestInterface $serverRequest, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $user = $serverRequest->getAttribute('user');
        $route = RouteContext::fromRequest($serverRequest)->getRoute();
        // @phpstan-ignore-next-line
        $permissions = $route?->getArgument('permissions');

        // Convert single permission to array
        // @phpstan-ignore-next-line
        if (! is_array($permissions)) {
            $permissions = [$permissions];
        }
        $hasPermission = array_any($permissions, fn ($permission): bool => $user && $user->hasPermission($permission));

        if (! $hasPermission) {
            $response = new Response();
            $json = json_encode([
                'error' => 'Unauthorized',
                'message' => 'You do not have the required permission to access this resource',
            ]);
            $response->getBody()->write($json !== false ? $json : '{}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        return $requestHandler->handle($serverRequest);
    }
}
