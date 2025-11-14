<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a resource is not found.
 */
class NotFoundException extends RuntimeException
{
    /**
     * Create a new NotFoundException instance.
     */
    public function __construct(string $message = 'Resource not found', int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
