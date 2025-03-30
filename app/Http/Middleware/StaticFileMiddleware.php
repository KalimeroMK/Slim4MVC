<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Stream;

class StaticFileMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $uri = $request->getUri()->getPath();

        if (!str_starts_with($uri, '/public/')) {
            return $handler->handle($request);
        }

        $file = __DIR__ . '/../../public' . str_replace('/public', '', $uri);

        if (!file_exists($file)) {
            throw new HttpNotFoundException($request);
        }

        $fh = fopen($file, 'rb');
        $stream = new Stream($fh);

        $response = new \Slim\Psr7\Response();
        return $response
            ->withBody($stream)
            ->withHeader('Content-Type', mime_content_type($file))
            ->withHeader('Content-Length', (string) filesize($file))
            ->withHeader('Cache-Control', 'public, max-age=86400');
    }
}
