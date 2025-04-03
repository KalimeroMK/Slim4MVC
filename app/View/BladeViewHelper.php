<?php

declare(strict_types=1);

use App\View\Blade;
use Psr\Http\Message\ResponseInterface as Response;

function view($template, Response $response, array $with = []): Response
{
    $cache = __DIR__.'/../../storage/cache/view';
    $views = __DIR__.'/../../resources/views';

    $blade = new Blade($views, $cache);

    $response->getBody()->write($blade->make($template, $with));

    return $response;
}
