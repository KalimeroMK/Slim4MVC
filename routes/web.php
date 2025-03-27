<?php

declare(strict_types=1);

return function ($app) {
    $app->get('/', function ($request, $response) {
        $response->getBody()->write('Hello from Slim 4!');
        return $response;
    });
};
