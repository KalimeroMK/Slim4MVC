<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthWebMiddleware implements MiddlewareInterface
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if (! $this->session->has('user')) {
            $response = new SlimResponse();

            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}
