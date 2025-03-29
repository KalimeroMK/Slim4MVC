<?php

use Psr\Http\Message\ResponseInterface as Response;
use Support\Blade;

if (!function_exists('view')) {
    function view(Response $response, $template, $with = []): Response
    {
        $cache = __DIR__ . '/../storage/cache/';
        $views = __DIR__ . '/../resources/views';

        $blade = new Blade($views, $cache);

        $response->getBody()->write($blade->make($template, $with));

        return $response;
    }
}