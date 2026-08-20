<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Modules\Core\Infrastructure\Http\Controllers\GenericCrudController;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\User\Infrastructure\Http\Resources\UserResource;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * Auth is required but no per-action permission is configured, so the guard should
 * fall through to the authentication check alone.
 */
final class OpenUserCrudController extends GenericCrudController
{
    protected string $repositoryClass = UserRepository::class;

    protected array $fillable = ['name', 'email'];
}

final class ResourceUserCrudController extends GenericCrudController
{
    protected string $repositoryClass = UserRepository::class;

    protected array $fillable = ['name', 'email'];

    protected ?string $resourceClass = UserResource::class;
}

final class RelationUserCrudController extends GenericCrudController
{
    protected string $repositoryClass = UserRepository::class;

    protected array $fillable = ['name', 'email'];

    protected array $defaultRelations = ['roles'];
}

/**
 * The rejection paths live in GenericCrudControllerSecurityTest; this covers what
 * happens once a request is allowed through.
 */
#[CoversClass(GenericCrudController::class)]
final class GenericCrudControllerTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory();
        $this->authenticate();
    }

    // -------------------------------------------------------------------- index

    public function test_index_returns_a_paginated_envelope(): void
    {
        $this->createUser(['email' => 'a@example.com']);
        $this->createUser(['email' => 'b@example.com']);

        $body = $this->json($this->index($this->controller(), '/api/v1/users'));

        $this->assertArrayHasKey('items', $body['data']);
        $this->assertArrayHasKey('pagination', $body['data']);
        $this->assertSame(3, $body['data']['pagination']['total']); // 2 + the logged-in user
        $this->assertSame(15, $body['data']['pagination']['per_page']);
    }

    public function test_index_honours_the_pagination_query(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createUser(['email' => sprintf('page-%d@example.com', $i)]);
        }

        $body = $this->json($this->index($this->controller(), '/api/v1/users?page=2&per_page=2'));

        $this->assertSame(2, $body['data']['pagination']['current_page']);
        $this->assertSame(2, $body['data']['pagination']['per_page']);
        $this->assertCount(2, $body['data']['items']);
    }

    public function test_index_applies_the_resource_class(): void
    {
        $body = $this->json($this->index(new ResourceUserCrudController($this->container), '/api/v1/users'));
        $first = $body['data']['items'][0];

        // UserResource exposes id/name/email and deliberately omits the password.
        $this->assertArrayHasKey('email', $first);
        $this->assertArrayNotHasKey('password', $first);
    }

    public function test_index_eager_loads_the_default_relations(): void
    {
        $body = $this->json($this->index(new RelationUserCrudController($this->container), '/api/v1/users'));

        $this->assertArrayHasKey('roles', $body['data']['items'][0]);
    }

    // --------------------------------------------------------------------- show

    public function test_show_returns_the_requested_record(): void
    {
        $user = $this->createUser(['email' => 'shown@example.com']);

        $body = $this->json($this->controller()->show(
            $this->request('GET', '/api/v1/users/'.$user->id),
            new Response(),
            ['id' => (string) $user->id]
        ));

        $this->assertSame('shown@example.com', $body['data']['email']);
    }

    public function test_show_without_an_id_is_a_bad_request(): void
    {
        $result = $this->controller()->show($this->request('GET', '/api/v1/users'), new Response(), []);

        $this->assertSame(400, $result->getStatusCode());
        $this->assertSame('ID is required', $this->json($result)['message']);
    }

    // -------------------------------------------------------------------- store

    public function test_store_persists_the_fillable_fields(): void
    {
        $result = $this->controller()->store(
            $this->request('POST', '/api/v1/users')->withParsedBody([
                'name' => 'Created',
                'email' => 'created@example.com',
            ]),
            new Response()
        );

        $this->assertSame(201, $result->getStatusCode());
        $this->assertDatabaseHas('users', ['email' => 'created@example.com', 'name' => 'Created']);
    }

    public function test_store_rejects_a_body_that_is_not_an_array(): void
    {
        $result = $this->controller()->store($this->request('POST', '/api/v1/users'), new Response());

        $this->assertSame(422, $result->getStatusCode());
    }

    // ------------------------------------------------------------------- update

    public function test_update_persists_the_change(): void
    {
        $user = $this->createUser(['name' => 'Before', 'email' => 'update@example.com']);

        $result = $this->controller()->update(
            $this->request('PUT', '/api/v1/users/'.$user->id)->withParsedBody(['name' => 'After']),
            new Response(),
            ['id' => (string) $user->id]
        );

        $this->assertSame(200, $result->getStatusCode());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'After']);
    }

    public function test_update_without_an_id_is_a_bad_request(): void
    {
        $result = $this->controller()->update(
            $this->request('PUT', '/api/v1/users')->withParsedBody(['name' => 'X']),
            new Response(),
            []
        );

        $this->assertSame(400, $result->getStatusCode());
    }

    public function test_update_with_nothing_fillable_left_is_unprocessable(): void
    {
        $user = $this->createUser(['email' => 'nofill@example.com']);

        $result = $this->controller()->update(
            $this->request('PUT', '/api/v1/users/'.$user->id)->withParsedBody(['password' => 'nope']),
            new Response(),
            ['id' => (string) $user->id]
        );

        $this->assertSame(422, $result->getStatusCode());
    }

    // ------------------------------------------------------------------ destroy

    public function test_destroy_removes_the_record(): void
    {
        $user = $this->createUser(['email' => 'gone@example.com']);

        $result = $this->controller()->destroy(
            $this->request('DELETE', '/api/v1/users/'.$user->id),
            new Response(),
            ['id' => (string) $user->id]
        );

        $this->assertSame(204, $result->getStatusCode());
        $this->assertNull(User::find($user->id));
    }

    public function test_destroy_without_an_id_is_a_bad_request(): void
    {
        $result = $this->controller()->destroy($this->request('DELETE', '/api/v1/users'), new Response(), []);

        $this->assertSame(400, $result->getStatusCode());
    }

    private function controller(): OpenUserCrudController
    {
        return new OpenUserCrudController($this->container);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true);

        return $decoded;
    }

    /**
     * Dispatch index() the way the framework does: the request is bound to the
     * controller by FormRequestStrategy, and that is where pagination is read from.
     */
    private function index(GenericCrudController $controller, string $target): ResponseInterface
    {
        $request = $this->request('GET', $target);
        $controller->setRequest($request);

        return $controller->index($request, new Response());
    }

    private function request(string $method, string $target): ServerRequestInterface
    {
        return $this->requestFactory->createServerRequest($method, $target);
    }

    private function authenticate(): void
    {
        $user = $this->createUser(['name' => 'Operator', 'email' => 'operator@example.com']);

        $auth = $this->createStub(Auth::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($user);
        $this->container->set(Auth::class, $auth);
    }
}
