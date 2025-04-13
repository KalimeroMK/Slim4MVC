<?php

// app/Http/Controllers/Controller.php

declare(strict_types=1);

namespace App\Http\Controllers;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Controller
{
    public function __construct(
        protected ContainerInterface $container
    ) {}

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function redirect(string $url): ResponseInterface
    {
        $response = $this->container->get(ResponseInterface::class);

        return $response->withHeader('Location', $url)->withStatus(302);
    }
}
