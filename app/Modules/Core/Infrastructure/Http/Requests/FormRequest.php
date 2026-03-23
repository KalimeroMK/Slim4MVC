<?php

// app/Http/Requests/FormRequest.php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Requests;

use App\Modules\Core\Infrastructure\Exceptions\ValidationException;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;

abstract class FormRequest
{
    /** @var array<string, mixed> */
    protected array $rules = [];

    /** @var array<string, string> */
    protected array $messages = [];

    protected Validator $validator;

    protected bool $isValidated = false;

    /** @var array<string, mixed> */
    protected array $validatedData = [];

    public function __construct(
        protected ServerRequestInterface $request,
        protected ValidatorFactory $validatorFactory
    ) {}

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(): array;

    /**
     * @return array<string, mixed>
     */
    final public function validated(): array
    {
        if (! $this->isValidated) {
            $this->validate();
        }

        return $this->validatedData;
    }

    final public function validate(): void
    {
        $this->validator = $this->validatorFactory->make(
            $this->data(),
            $this->rules(),
            $this->messages()
        );

        if ($this->validator->fails()) {
            // Format errors by field for better API response
            $errors = [];
            foreach ($this->validator->errors()->messages() as $field => $messages) {
                $errors[$field] = $messages;
            }

            $response = ApiResponse::validationError($errors);

            // Throw an exception that can be caught by middleware
            throw new ValidationException($response);
        }

        $this->isValidated = true;
        $this->validatedData = $this->validator->validated();
    }

    /**
     * @return array<string, mixed>
     */
    protected function data(): array
    {
        /** @var array<string, mixed>|null $body */
        $body = $this->request->getParsedBody();

        return $body ?? [];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return $this->messages;
    }
}
