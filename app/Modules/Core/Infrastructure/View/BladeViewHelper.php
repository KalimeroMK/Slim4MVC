<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\View\Blade;
use Psr\Http\Message\ResponseInterface as Response;

if (! function_exists('view')) {
    /**
     * Render a Blade view template.
     *
     * @param string $template
     * @param Response $response
     * @param array<string, mixed> $with
     * @return Response
     */
    function view(string $template, Response $response, array $with = []): Response
    {
        $cache = __DIR__.'/../../../../storage/cache/view';
        $views = __DIR__.'/../../../../resources/views';

        $blade = new Blade($views, $cache);

        $response->getBody()->write($blade->make($template, $with));

        return $response;
    }
}
