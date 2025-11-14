<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when access is forbidden.
 */
class ForbiddenException extends RuntimeException
{
    /**
     * Create a new ForbiddenException instance.
     */
    public function __construct(string $message = 'Forbidden', int $code = 403, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
