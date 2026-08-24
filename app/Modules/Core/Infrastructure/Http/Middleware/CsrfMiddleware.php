<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Http\Exceptions\HttpTokenMismatchException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Random\RandomException;
use Slim\Exception\HttpBadRequestException;

class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @throws RandomException
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $uri = $request->getUri()->getPath();

        if (str_starts_with($uri, '/api/')) {
            return $handler->handle($request);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            /** @var array<string, mixed>|object|null $data */
            $data = $request->getParsedBody();
            if (! is_array($data)) {
                throw new HttpBadRequestException($request, 'Invalid request body.');
            }

            $token = $data['_token'] ?? null;

            if (! is_string($token) || ! isset($_SESSION['csrf_token']) || ! hash_equals($_SESSION['csrf_token'], $token)) {
                throw new HttpTokenMismatchException($request);
            }
        }

        return $handler->handle($request);
    }
}
