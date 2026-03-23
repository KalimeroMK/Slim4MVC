<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidationExceptionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Let ExceptionHandlerMiddleware handle validation exceptions
        // This middleware is now just a pass-through
        return $handler->handle($request);
    }
}
