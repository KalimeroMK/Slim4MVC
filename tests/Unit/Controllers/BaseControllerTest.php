<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * Exposes the base controller's protected helpers so they can be exercised directly.
 */
final class BaseControllerProbe extends Controller
{
    public function callGetRequest(): ServerRequestInterface
    {
        return $this->getRequest();
    }

    /**
     * @return array{page: int, perPage: int}
     */
    public function callPaginationParams(): array
    {
        return $this->getPaginationParams();
    }

    public function callPaginationBaseUrl(): string
    {
        return $this->getPaginationBaseUrl();
    }

    public function callRedirect(string $url): ResponseInterface
    {
        return $this->redirect($url);
    }

    public function callRespondWithJson(mixed $data, int $status = 200): ResponseInterface
    {
        return $this->respondWithJson($data, $status);
    }

    public function callRespondUnauthorized(): ResponseInterface
    {
        return $this->respondUnauthorized();
    }

    public function callNotFound(?string $message = null): ResponseInterface
    {
        return $this->notFound($message);
    }

    public function callBadRequest(string $message = 'Bad Request'): ResponseInterface
    {
        return $this->badRequest($message);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public function callValidationError(array $errors, ?string $message = null): ResponseInterface
    {
        return $this->validationError($errors, $message);
    }

    public function callGetContainer(): \Psr\Container\ContainerInterface
    {
        return $this->getContainer();
    }
}

#[CoversClass(Controller::class)]
final class BaseControllerTest extends TestCase
{
    private BaseControllerProbe $controller;

    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory();
        $this->container->set(ResponseInterface::class, fn (): ResponseInterface => new Response());
        $this->controller = new BaseControllerProbe($this->container);
    }

    public function test_it_returns_the_request_that_was_set(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/v1/users');
        $this->controller->setRequest($request);

        $this->assertSame($request, $this->controller->callGetRequest());
    }

    public function test_it_refuses_to_invent_a_request(): void
    {
        // FormRequestStrategy is what injects it; silently returning something else
        // would hide a wiring mistake.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request not available');

        $this->controller->callGetRequest();
    }

    public function test_it_exposes_the_container(): void
    {
        $this->assertSame($this->container, $this->controller->callGetContainer());
    }

    public function test_pagination_defaults_to_the_first_page(): void
    {
        $this->controller->setRequest($this->requestFactory->createServerRequest('GET', '/api/v1/users'));

        $this->assertSame(['page' => 1, 'perPage' => 15], $this->controller->callPaginationParams());
    }

    public function test_pagination_reads_the_query_string(): void
    {
        $this->controller->setRequest($this->request('/api/v1/users?page=4&per_page=25'));

        $this->assertSame(['page' => 4, 'perPage' => 25], $this->controller->callPaginationParams());
    }

    public function test_per_page_is_capped_at_one_hundred(): void
    {
        // An unbounded per_page lets a caller ask for the whole table in one request.
        $this->controller->setRequest($this->request('/api/v1/users?per_page=5000'));

        $this->assertSame(100, $this->controller->callPaginationParams()['perPage']);
    }

    public function test_non_positive_pagination_values_are_clamped(): void
    {
        $this->controller->setRequest($this->request('/api/v1/users?page=-3&per_page=0'));

        $this->assertSame(['page' => 1, 'perPage' => 1], $this->controller->callPaginationParams());
    }

    public function test_garbage_pagination_values_fall_back_to_the_minimum(): void
    {
        $this->controller->setRequest($this->request('/api/v1/users?page=abc&per_page=xyz'));

        $this->assertSame(['page' => 1, 'perPage' => 1], $this->controller->callPaginationParams());
    }

    public function test_pagination_base_url_keeps_the_query_string(): void
    {
        $this->controller->setRequest($this->request('/api/v1/users?page=2&sort=name'));

        $this->assertSame('/api/v1/users?page=2&sort=name', $this->controller->callPaginationBaseUrl());
    }

    public function test_pagination_base_url_without_a_query_string(): void
    {
        $this->controller->setRequest($this->requestFactory->createServerRequest('GET', '/api/v1/users'));

        $this->assertSame('/api/v1/users', $this->controller->callPaginationBaseUrl());
    }

    public function test_redirect_sends_a_302_with_a_location(): void
    {
        $response = $this->controller->callRedirect('/login');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function test_json_response_carries_body_type_and_status(): void
    {
        $response = $this->controller->callRespondWithJson(['ok' => true], 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['ok' => true], json_decode((string) $response->getBody(), true));
    }

    public function test_json_response_falls_back_to_an_empty_object_when_encoding_fails(): void
    {
        // NAN cannot be encoded; the response must still be valid JSON.
        $response = $this->controller->callRespondWithJson(NAN);

        $this->assertSame('{}', (string) $response->getBody());
    }

    public function test_error_helpers_use_the_documented_status_codes(): void
    {
        $this->assertSame(403, $this->controller->callRespondUnauthorized()->getStatusCode());
        $this->assertSame(404, $this->controller->callNotFound()->getStatusCode());
        $this->assertSame(400, $this->controller->callBadRequest()->getStatusCode());
        $this->assertSame(422, $this->controller->callValidationError(['email' => 'required'])->getStatusCode());
    }

    public function test_error_helpers_carry_their_messages(): void
    {
        $notFound = json_decode((string) $this->controller->callNotFound('No such widget')->getBody(), true);
        $badRequest = json_decode((string) $this->controller->callBadRequest('Nope')->getBody(), true);

        $this->assertSame('No such widget', $notFound['message']);
        $this->assertSame('Nope', $badRequest['message']);
    }

    public function test_validation_error_reports_the_offending_fields(): void
    {
        $response = $this->controller->callValidationError(['email' => 'required'], 'Check your input');
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame('Check your input', $body['message']);
        $this->assertSame(['email' => 'required'], $body['errors']);
    }

    private function request(string $target): ServerRequestInterface
    {
        return $this->requestFactory->createServerRequest('GET', $target);
    }
}
