<?php

declare(strict_types=1);

use App\View\Blade;
use Psr\Http\Message\ResponseInterface as Response;

if (! function_exists('view')) {
    function view(Response $response, $template, $with = []): Response
    {
        $cache = __DIR__.'/../../storage/cache/';
        $views = __DIR__.'/../../resources/views';

        $blade = new Blade($views, $cache);

        $response->getBody()->write($blade->make($template, $with));

        return $response;
    }
}
