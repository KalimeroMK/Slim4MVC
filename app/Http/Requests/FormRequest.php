<?php

// app/Http/Requests/FormRequest.php

declare(strict_types=1);

namespace App\Http\Requests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

abstract class FormRequest
{
    protected array $rules = [];

    protected array $messages = [];

    public function __construct(
        protected ServerRequestInterface $request,
        protected $validator // Illuminate Validator instance
    ) {}

    abstract protected function rules(): array;

    final public function validate(): ?ResponseInterface
    {
        $validator = $this->validator->make(
            $this->data(),
            $this->rules(),
            $this->messages()
        );

        if ($validator->fails()) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'errors' => $validator->errors()->all(),
            ]));

            return $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }

        return null;
    }

    final public function validated(): array
    {
        return $this->data();
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
