<?php

// src/Middleware/CsrfMiddleware.php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * CSRF Protection Middleware
 *
 * - Skips API routes (/api/*)
 * - Generates CSRF token for session if not exists
 * - Validates CSRF token on state-changing requests (POST, PUT, PATCH, DELETE)
 */
class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ?Session $session = null
    ) {}

    /**
     * @throws RandomException
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $uri = $request->getUri()->getPath();

        // Skip CSRF for API routes (they use JWT authentication)
        if (str_starts_with($uri, '/api/')) {
            return $handler->handle($request);
        }

        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate CSRF token if not exists
        if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Only validate CSRF token for state-changing requests
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            /** @var array<string, mixed>|object|null $data */
            $data = $request->getParsedBody();
            if (! is_array($data)) {
                throw new RuntimeException('Invalid request body', 400);
            }
            $token = $data['_token'] ?? null;

            if (! $token || $token !== $_SESSION['csrf_token']) {
                throw new RuntimeException('CSRF token mismatch', 419);
            }
        }

        return $handler->handle($request);
    }
}
