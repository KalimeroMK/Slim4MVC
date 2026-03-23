<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;
use Illuminate\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;

class RequestResolver
{
    public function __construct(
        private readonly Factory $validatorFactory
    ) {}

    public function resolve(string $requestClass, ServerRequestInterface $serverRequest): FormRequest
    {
        /** @var FormRequest $instance */
        $instance = new $requestClass($serverRequest, $this->validatorFactory);

        return $instance;
    }
}
