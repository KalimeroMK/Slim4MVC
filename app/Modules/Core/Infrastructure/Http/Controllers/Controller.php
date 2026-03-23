<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers;

use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Core\Infrastructure\Traits\AuthorizesRequests;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Current request (set by FormRequestStrategy).
     */
    protected ?Request $currentRequest = null;

    public function __construct(protected ContainerInterface $container) {}

    /**
     * Set the current request.
     */
    final public function setRequest(Request $request): void
    {
        $this->currentRequest = $request;
    }

    /**
     * Get the container instance.
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the request from container.
     */
    protected function getRequest(): Request
    {
        if ($this->currentRequest !== null) {
            return $this->currentRequest;
        }

        // Fallback for edge cases
        throw new RuntimeException('Request not available. Ensure FormRequestStrategy is used.');
    }

    /**
     * Get a fresh response instance.
     */
    protected function getResponse(): Response
    {
        return $this->container->get(Response::class);
    }

    /**
     * Redirect to a specific URL.
     */
    protected function redirect(string $url): Response
    {
        $response = $this->getResponse();

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }

    /**
     * Return a JSON response.
     */
    protected function respondWithJson(mixed $data, int $status = 200): Response
    {
        $response = $this->getResponse();
        $json = json_encode($data);
        $response->getBody()->write($json !== false ? $json : '{}');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Return a 403 Forbidden response.
     */
    protected function respondUnauthorized(): Response
    {
        return ApiResponse::forbidden('You are not authorized to perform this action');
    }

    /**
     * Return a 404 Not Found response.
     */
    protected function notFound(?string $message = null): Response
    {
        return ApiResponse::notFound($message ?? 'The requested resource was not found');
    }

    /**
     * Return a 400 Bad Request response.
     */
    protected function badRequest(string $message = 'Bad Request'): Response
    {
        return ApiResponse::badRequest($message);
    }

    /**
     * Return a 422 Unprocessable Entity response.
     */
    /**
     * @param  array<string, mixed>  $errors
     */
    protected function validationError(array $errors, ?string $message = null): Response
    {
        return ApiResponse::validationError($errors, $message);
    }

    /**
     * Get pagination parameters from request.
     *
     * @return array{page: int, perPage: int}
     */
    protected function getPaginationParams(): array
    {
        $request = $this->getRequest();
        $queryParams = $request->getQueryParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($queryParams['per_page'] ?? 15))); // Max 100 per page

        return [
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Get base URL for pagination links.
     */
    protected function getPaginationBaseUrl(): string
    {
        $request = $this->getRequest();
        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();

        return $path.($query ? '?'.$query : '');
    }
}
