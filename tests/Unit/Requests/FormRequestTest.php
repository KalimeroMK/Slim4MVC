<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Exceptions\ValidationException;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class FormRequestTest extends TestCase
{
    private Factory $validatorFactory;

    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validatorFactory = new Factory(
            new \Illuminate\Translation\Translator(
                new \Illuminate\Translation\ArrayLoader(),
                'en'
            )
        );
        $this->requestFactory = new ServerRequestFactory();
    }

    public function test_validated_returns_validated_data_when_validation_passes(): void
    {
        $request = $this->createRequest(['email' => 'test@example.com', 'password' => 'password123']);
        $formRequest = new TestFormRequest($request, $this->validatorFactory);

        $validated = $formRequest->validated();

        $this->assertEquals('test@example.com', $validated['email']);
        $this->assertEquals('password123', $validated['password']);
    }

    public function test_validated_throws_exception_when_validation_fails(): void
    {
        $request = $this->createRequest(['email' => 'invalid-email', 'password' => '']);
        $formRequest = new TestFormRequest($request, $this->validatorFactory);

        $this->expectException(ValidationException::class);

        $formRequest->validated();
    }

    public function test_validate_throws_exception_with_422_status(): void
    {
        $request = $this->createRequest(['email' => 'invalid']);
        $formRequest = new TestFormRequest($request, $this->validatorFactory);

        try {
            $formRequest->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $response = $e->getResponse();
            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('errors', (string) $response->getBody());
        }
    }

    private function createRequest(array $body): ServerRequestInterface
    {
        $request = $this->requestFactory->createServerRequest('POST', '/test');

        return $request->withParsedBody($body);
    }
}

// Test implementation of FormRequest
class TestFormRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ];
    }
}
