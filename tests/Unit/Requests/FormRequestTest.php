<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Modules\Core\Infrastructure\Exceptions\ValidationException;
use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;
use Illuminate\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

final class FormRequestTest extends TestCase
{
    private Factory $validatorFactory;

    private ServerRequestFactory $serverRequestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validatorFactory = new Factory(
            new \Illuminate\Translation\Translator(
                new \Illuminate\Translation\ArrayLoader(),
                'en'
            )
        );
        $this->serverRequestFactory = new ServerRequestFactory();
    }

    public function test_validated_returns_validated_data_when_validation_passes(): void
    {
        $serverRequest = $this->createRequest(['email' => 'test@example.com', 'password' => 'password123']);
        $testFormRequest = new TestFormRequest($serverRequest, $this->validatorFactory);

        $validated = $testFormRequest->validated();

        $this->assertEquals('test@example.com', $validated['email']);
        $this->assertEquals('password123', $validated['password']);
    }

    public function test_validated_throws_exception_when_validation_fails(): void
    {
        $serverRequest = $this->createRequest(['email' => 'invalid-email', 'password' => '']);
        $testFormRequest = new TestFormRequest($serverRequest, $this->validatorFactory);

        $this->expectException(ValidationException::class);

        $testFormRequest->validated();
    }

    public function test_validate_throws_exception_with_422_status(): void
    {
        $serverRequest = $this->createRequest(['email' => 'invalid']);
        $testFormRequest = new TestFormRequest($serverRequest, $this->validatorFactory);

        try {
            $testFormRequest->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $validationException) {
            $response = $validationException->getResponse();
            $this->assertEquals(422, $response->getStatusCode());
            $this->assertStringContainsString('errors', (string) $response->getBody());
        }
    }

    private function createRequest(array $body): ServerRequestInterface
    {
        $request = $this->serverRequestFactory->createServerRequest('POST', '/test');

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
