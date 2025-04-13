<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;

class RequestResolver
{
    public function __construct(
        private readonly Factory $validatorFactory
    ) {}

    public function resolve(string $requestClass, ServerRequestInterface $request): FormRequest
    {
        return new $requestClass($request, $this->validatorFactory);
    }
}
