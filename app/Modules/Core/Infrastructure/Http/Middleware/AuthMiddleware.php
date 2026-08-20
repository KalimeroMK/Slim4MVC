<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Core\Infrastructure\Support\Auth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Token-only guard for API routes.
 *
 * Session cookies are deliberately rejected here: CSRF validation is skipped for
 * /api/ paths, so accepting the session cookie would leave every API endpoint
 * reachable from any other site. Use AuthWebMiddleware for cookie-based routes.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(protected Auth $auth) {}

    public function process(Request $request, Handler $handler): Response
    {
        $this->auth->setRequest($request, allowSession: false);

        if (! $this->auth->check()) {
            return ApiResponse::unauthorized();
        }

        return $handler->handle($request);
    }
}
