<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when invalid credentials are provided.
 */
class InvalidCredentialsException extends RuntimeException
{
    /**
     * Create a new InvalidCredentialsException instance.
     */
    public function __construct(string $message = 'Invalid credentials', int $code = 401, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
