<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a user is not authorized to perform an action.
 */
class UnauthorizedException extends RuntimeException
{
    /**
     * Create a new UnauthorizedException instance.
     */
    public function __construct(string $message = 'Unauthorized', int $code = 401, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
