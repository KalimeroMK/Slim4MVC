<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class ValidationException extends Exception
{
    public function __construct(private readonly ResponseInterface $response)
    {
        parent::__construct('The given data was invalid.');
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
