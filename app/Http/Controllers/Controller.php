<?php

// app/Http/Controllers/Controller.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Traits\AuthorizesRequests;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class Controller
{
    use AuthorizesRequests;

    protected Request $request;

    protected Response $response;

    public function __construct(
        protected ContainerInterface $container,
        Request $request,
        Response $response
    ) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Get the container instance.
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Redirect to a specific URL.
     */
    protected function redirect(string $url): Response
    {
        return $this->response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }

    /**
     * Return a JSON response.
     */
    protected function respondWithJson(mixed $data, int $status = 200): Response
    {
        $this->response->getBody()->write(json_encode($data));

        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Return a 403 Forbidden response.
     */
    protected function respondUnauthorized(): Response
    {
        return $this->respondWithJson([
            'error' => 'Unauthorized',
            'message' => 'You are not authorized to perform this action',
        ], 403);
    }

    /**
     * Return a 404 Not Found response.
     */
    protected function notFound(): Response
    {
        return $this->respondWithJson([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found',
        ], 404);
    }

    /**
     * Return a 400 Bad Request response.
     */
    protected function badRequest(string $message = 'Bad Request'): Response
    {
        return $this->respondWithJson([
            'error' => 'Bad Request',
            'message' => $message,
        ], 400);
    }

    /**
     * Return a 422 Unprocessable Entity response.
     */
    protected function validationError(array $errors): Response
    {
        return $this->respondWithJson([
            'error' => 'Validation Error',
            'message' => 'The given data was invalid',
            'errors' => $errors,
        ], 422);
    }
}
