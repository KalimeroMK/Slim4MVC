<?php

// src/Middleware/CsrfMiddleware.php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Random\RandomException;
use RuntimeException;

class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @throws RandomException
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $token = $data['_token'] ?? null;

            if (! $token || $token !== $_SESSION['csrf_token']) {
                throw new RuntimeException('CSRF token mismatch', 419);
            }
        }

        return $handler->handle($request);
    }
}
