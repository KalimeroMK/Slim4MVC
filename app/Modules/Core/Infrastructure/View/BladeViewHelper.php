<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\View\Blade;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

if (! function_exists('view')) {
    /**
     * Render a Blade view template.
     *
     * @param  array<string, mixed>  $with
     */
    function view(string $template, Response $response, array $with = []): Response
    {
        $app = app();

        if ($app instanceof ContainerInterface && $app->has(Blade::class)) {
            $blade = $app->get(Blade::class);
        } else {
            $cache = __DIR__.'/../../../../../storage/cache/view';
            $views = __DIR__.'/../../../../../resources/views';
            $blade = new Blade($views, $cache);
        }

        $response->getBody()->write($blade->make($template, $with));

        return $response;
    }
}

if (! function_exists('app')) {
    /**
     * Get the container instance.
     */
    function app(): ?ContainerInterface
    {
        global $app;

        if (isset($app) && $app instanceof Slim\App) {
            return $app->getContainer();
        }

        return null;
    }
}
