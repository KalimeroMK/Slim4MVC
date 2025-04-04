<?php

// app/Http/Requests/FormRequest.php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

abstract class FormRequest
{
    protected array $rules = [];

    protected array $messages = [];

    protected Validator $validator;

    public function __construct(
        protected ServerRequestInterface $request,
        protected ValidatorFactory $validatorFactory
    ) {
        $this->validator = $this->validatorFactory->make(
            $this->data(),
            $this->rules(),
            $this->messages()
        );
    }

    abstract protected function rules(): array;

    final public function validate(): ?ResponseInterface
    {
        if ($this->validator->fails()) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'errors' => $this->validator->errors()->all(),
            ]));

            return $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }

        return null;
    }

    final public function validator()
    {
        return $this->validator;
    }

    final public function validated(): array
    {
        return $this->validator->validated();
    }

    protected function data(): array
    {
        return $this->request->getParsedBody() ?? [];
    }

    protected function messages(): array
    {
        return $this->messages;
    }
}
