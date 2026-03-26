<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\DI;

use RuntimeException;

/**
 * Exception thrown during dependency discovery.
 */
class DiscoveryException extends RuntimeException
{
}
