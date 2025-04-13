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

    protected ContainerInterface $container;
    protected Request $request;
    protected Response $response;

    public function __construct(
        protected ContainerInterface $container,
        Request $request,
        Response $response
    ) {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    protected function redirect(string $url): Response
    {
        $response = $this->container->get(Response::class);
        return $response->withHeader('Location', $url)->withStatus(302);
    }

    /**
     * Return a 403 Forbidden response
     */
    protected function respondUnauthorized(): Response
    {
        $this->response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => 'You are not authorized to perform this action'
        ]));

        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
}
