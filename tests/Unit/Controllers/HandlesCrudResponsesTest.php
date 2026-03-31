<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Modules\Core\Infrastructure\Http\Controllers\Concerns\HandlesCrudResponses;
use App\Modules\Core\Infrastructure\Http\Resources\Resource;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Test controller that uses HandlesCrudResponses trait
 */
class TestCrudController
{
    use HandlesCrudResponses;

    protected ?string $resourceClass = TestResource::class;

    protected function getPaginationBaseUrl(): string
    {
        return '/api/test';
    }

    // Expose protected methods for testing
    public function testRespondPaginated(array $result, ?string $resourceClass = null): ResponseInterface
    {
        return $this->respondPaginated($result, $resourceClass);
    }

    public function testRespondResource(mixed $model, ?string $resourceClass = null): ResponseInterface
    {
        return $this->respondResource($model, $resourceClass);
    }

    public function testRespondCreated(mixed $model, ?string $resourceClass = null): ResponseInterface
    {
        return $this->respondCreated($model, $resourceClass);
    }

    public function testRespondUpdated(mixed $model, ?string $resourceClass = null): ResponseInterface
    {
        return $this->respondUpdated($model, $resourceClass);
    }

    public function testRespondDeleted(): ResponseInterface
    {
        return $this->respondDeleted();
    }
}

/**
 * Test resource class
 */
class TestResource extends Resource
{
    public static function make(mixed $resource): array
    {
        return [
            'id' => $resource['id'] ?? null,
            'name' => $resource['name'] ?? null,
        ];
    }
}

/**
 * @covers \App\Modules\Core\Infrastructure\Http\Controllers\Concerns\HandlesCrudResponses
 */
final class HandlesCrudResponsesTest extends TestCase
{
    private TestCrudController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestCrudController();
    }

    public function test_respond_paginated_returns_json_response(): void
    {
        $result = [
            'items' => [['id' => 1, 'name' => 'Test 1'], ['id' => 2, 'name' => 'Test 2']],
            'total' => 10,
            'page' => 1,
            'perPage' => 15,
        ];

        $response = $this->controller->testRespondPaginated($result);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function test_respond_resource_returns_json_response(): void
    {
        $model = ['id' => 1, 'name' => 'Test'];

        $response = $this->controller->testRespondResource($model);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_respond_created_returns_201_status(): void
    {
        $model = ['id' => 1, 'name' => 'Test'];

        $response = $this->controller->testRespondCreated($model);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_respond_updated_returns_200_status(): void
    {
        $model = ['id' => 1, 'name' => 'Updated'];

        $response = $this->controller->testRespondUpdated($model);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_respond_deleted_returns_204_status(): void
    {
        $response = $this->controller->testRespondDeleted();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_respond_paginated_uses_resource_class(): void
    {
        $result = [
            'items' => [['id' => 1, 'name' => 'Test']],
            'total' => 1,
            'page' => 1,
            'perPage' => 15,
        ];

        $response = $this->controller->testRespondPaginated($result);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('items', $body['data']);
        $this->assertArrayHasKey('pagination', $body['data']);
    }

    public function test_respond_paginated_accepts_custom_resource_class(): void
    {
        $result = [
            'items' => [['id' => 1, 'name' => 'Test']],
            'total' => 1,
            'page' => 1,
            'perPage' => 15,
        ];

        $response = $this->controller->testRespondPaginated($result, TestResource::class);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
