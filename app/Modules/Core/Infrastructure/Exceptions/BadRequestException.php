<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a bad request is made.
 */
class BadRequestException extends RuntimeException
{
    /**
     * Create a new BadRequestException instance.
     */
    public function __construct(string $message = 'Bad Request', int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
