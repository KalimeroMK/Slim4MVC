<?php

declare(strict_types=1);

namespace Tests\Feature;

use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Tests\TestCase;

/**
 * Base test case for API feature tests.
 */
abstract class ApiTestCase extends TestCase
{
    protected App $app;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Slim app instance for testing
        AppFactory::setContainer($this->container);
        $this->app = AppFactory::create();
    }

    /**
     * Make an API request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $data  Request data
     * @param  array<string, string>  $headers  Request headers
     */
    protected function request(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): ResponseInterface {
        $request = $this->app->getContainer()->get('request')
            ->withMethod($method)
            ->withUri($this->app->getContainer()->get('uri')->create($uri));

        if (! empty($data)) {
            $request = $request->withParsedBody($data);
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->app->handle($request);
    }

    /**
     * Assert response status code.
     */
    protected function assertResponseStatus(ResponseInterface $response, int $expectedStatus): void
    {
        $this->assertEquals($expectedStatus, $response->getStatusCode());
    }

    /**
     * Assert response is JSON.
     */
    protected function assertJsonResponse(ResponseInterface $response): array
    {
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertNotNull($data, 'Response body is not valid JSON');

        return $data;
    }

    /**
     * Assert response contains key.
     */
    protected function assertResponseHasKey(array $data, string $key): void
    {
        $this->assertArrayHasKey($key, $data);
    }
}
