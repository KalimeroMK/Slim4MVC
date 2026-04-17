<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Middleware to limit the maximum request body size.
 * Prevents large payload attacks and out-of-memory issues.
 */
class RequestSizeLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param  int  $maxBodySize  Maximum body size in bytes (default: 10MB)
     */
    public function __construct(
        private readonly int $maxBodySize = 10 * 1024 * 1024
    ) {}

    public function process(Request $request, Handler $handler): Response
    {
        $bodySize = $request->getBody()->getSize();

        // Prefer Content-Length header when available (it reflects the true payload size)
        $contentLength = $request->getHeaderLine('Content-Length');
        if ($contentLength !== '' && is_numeric($contentLength)) {
            $bodySize = (int) $contentLength;
        }

        // If still unknown, read content and create a new stream
        if ($bodySize === null) {
            $content = (string) $request->getBody();
            $bodySize = mb_strlen($content, '8bit');

            // Restore body stream since reading it may have exhausted it
            $streamFactory = new StreamFactory();
            $newStream = $streamFactory->createStream($content);
            $request = $request->withBody($newStream);
        }

        if ($bodySize > $this->maxBodySize) {
            return ApiResponse::error(
                sprintf(
                    'Request body too large. Maximum allowed size is %d bytes (%s).',
                    $this->maxBodySize,
                    $this->formatBytes($this->maxBodySize)
                ),
                413
            );
        }

        return $handler->handle($request);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }
}
