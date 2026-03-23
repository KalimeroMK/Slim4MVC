<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Exceptions\BadRequestException;
use App\Modules\Core\Infrastructure\Exceptions\ForbiddenException;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Exceptions\UnauthorizedException;
use App\Modules\Core\Infrastructure\Exceptions\ValidationException;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use RuntimeException;
use Throwable;

/**
 * Middleware to handle exceptions and convert them to appropriate API responses.
 */
class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and handle exceptions.
     */
    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundException $e) {
            return $this->isApiRequest($request)
                ? ApiResponse::notFound($e->getMessage())
                : $this->redirectResponse($request, '/404', 404);
        } catch (UnauthorizedException|InvalidCredentialsException $e) {
            return $this->isApiRequest($request)
                ? ApiResponse::unauthorized($e->getMessage())
                : $this->redirectResponse($request, '/login?error=unauthorized', 302);
        } catch (ForbiddenException $e) {
            return $this->isApiRequest($request)
                ? ApiResponse::forbidden($e->getMessage())
                : $this->redirectResponse($request, '/403', 403);
        } catch (BadRequestException $e) {
            return $this->isApiRequest($request)
                ? ApiResponse::badRequest($e->getMessage())
                : $this->redirectResponse($request, '/400?message='.urlencode($e->getMessage()), 302);
        } catch (ValidationException $e) {
            // For API: return JSON error
            // For Web: redirect back with error message
            if ($this->isApiRequest($request)) {
                return $e->getResponse();
            }

            // For web requests, redirect back with error
            $_SESSION['errors'] = ['validation' => 'The given data was invalid.'];
            $_SESSION['old_input'] = $request->getParsedBody() ?? [];

            $referer = $request->getHeaderLine('Referer') ?: '/';

            return $this->redirectResponse($request, $referer, 302);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->isApiRequest($request)
                ? ApiResponse::notFound('Resource not found')
                : $this->redirectResponse($request, '/404', 404);
        } catch (RuntimeException $e) {
            // Log the exception for debugging
            error_log('RuntimeException: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());

            return $this->isApiRequest($request)
                ? ApiResponse::error($e->getMessage(), 500)
                : $this->redirectResponse($request, '/500?message='.urlencode($e->getMessage()), 302);
        } catch (Throwable $e) {
            // Log unexpected exceptions
            error_log('Unexpected exception: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());

            return $this->isApiRequest($request)
                ? ApiResponse::error('An unexpected error occurred', 500)
                : $this->redirectResponse($request, '/500', 500);
        }
    }

    /**
     * Check if request is an API request.
     */
    private function isApiRequest(Request $request): bool
    {
        $path = $request->getUri()->getPath();

        return str_starts_with($path, '/api');
    }

    /**
     * Create a redirect response.
     */
    private function redirectResponse(Request $request, string $url, int $status): Response
    {
        $response = new \Slim\Psr7\Response($status);

        return $response->withHeader('Location', $url)->withStatus($status);
    }
}
