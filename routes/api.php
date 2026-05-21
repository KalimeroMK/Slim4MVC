<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    $app->get('/health', function (Request $request, Response $response): Response {
        $status = 'ok';
        $dbStatus = 'ok';
        $httpCode = 200;

        try {
            Capsule::connection()->getPdo();
        } catch (\Throwable) {
            $dbStatus = 'error';
            $status = 'degraded';
            $httpCode = 503;
        }

        $payload = json_encode([
            'status' => $status,
            'checks' => ['database' => $dbStatus],
            'timestamp' => date('c'),
        ]);

        $response->getBody()->write((string) $payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($httpCode);
    });
};
