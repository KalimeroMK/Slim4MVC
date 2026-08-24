<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Exceptions;

use Slim\Exception\HttpSpecializedException;

/**
 * The CSRF token was missing or did not match.
 *
 * 419 is not in the HTTP standard; it is the status Laravel returns for an expired
 * page, and CsrfMiddleware was written to use it. Slim ships no class for it, and a
 * plain RuntimeException carrying 419 as its exception code is not something the
 * error middleware reads - it answered 500 instead, so the intended status never
 * reached the browser even though the request was correctly refused.
 */
final class HttpTokenMismatchException extends HttpSpecializedException
{
    /** @var int */
    protected $code = 419;

    /** @var string */
    protected $message = 'CSRF token mismatch.';

    protected string $title = '419 Page Expired';

    protected string $description = 'The form has expired. Reload the page and submit it again.';
}
