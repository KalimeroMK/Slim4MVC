<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Modules\Core\Infrastructure\Http\Controllers\GenericCrudController;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * Concrete test controller extending GenericCrudController for security testing.
 */
final class TestUserCrudController extends GenericCrudController
{
    protected string $repositoryClass = UserRepository::class;

    protected array $fillable = ['name', 'email'];

    protected ?string $createPermission = 'users.create';

    protected ?string $updatePermission = 'users.update';

    protected ?string $deletePermission = 'users.delete';

    protected ?string $listPermission = 'users.viewAny';

    protected ?string $viewPermission = 'users.view';
}

/**
 * Test controller with NO fillable defined (insecure by default).
 */
final class InsecureTestUserCrudController extends GenericCrudController
{
    protected string $repositoryClass = UserRepository::class;
    // fillable intentionally left empty — should reject all data
}

/**
 * @covers \App\Modules\Core\Infrastructure\Http\Controllers\GenericCrudController
 */
final class GenericCrudControllerSecurityTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestFactory = new ServerRequestFactory();
        $_ENV['JWT_SECRET'] = 'test-secret-key-that-is-at-least-32-chars';
    }

    public function test_store_rejects_request_when_not_authenticated(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(false);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('POST', '/users');
        $response = new Response();

        $result = $controller->store($request, $response);

        $this->assertSame(401, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame('Authentication required', $body['message']);
    }

    public function test_store_rejects_request_without_permission(): void
    {
        $user = $this->createUser(['name' => 'Test', 'email' => 'test@example.com']);
        $this->simulateLogin($user);

        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($user);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('POST', '/users');
        $response = new Response();

        $result = $controller->store($request, $response);

        $this->assertSame(403, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame('You do not have permission to perform this action', $body['message']);
    }

    public function test_store_filters_non_fillable_fields(): void
    {
        $user = $this->createUser(['name' => 'Admin', 'email' => 'admin@example.com']);
        $adminRole = $this->createRole(['name' => 'admin']);
        $user->roles()->attach($adminRole->id);
        $this->simulateLogin($user, ['admin'], ['users.create']);

        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($user);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('POST', '/users')
            ->withParsedBody([
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'should_be_ignored',
                'is_admin' => true,
            ]);
        $response = new Response();

        $result = $controller->store($request, $response);

        $this->assertSame(201, $result->getStatusCode());

        // Verify that password and is_admin were NOT stored
        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'name' => 'New User']);
        $created = User::where('email', 'new@example.com')->first();
        $this->assertNull($created->password);
    }

    public function test_store_returns_error_when_fillable_is_empty(): void
    {
        $user = $this->createUser(['name' => 'Admin', 'email' => 'admin@example.com']);
        $this->simulateLogin($user);

        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($user);
        $this->container->set(Auth::class, $auth);

        $controller = new InsecureTestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('POST', '/users')
            ->withParsedBody(['name' => 'Hacker', 'email' => 'hack@example.com']);
        $response = new Response();

        $result = $controller->store($request, $response);

        $this->assertSame(500, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertStringContainsString('No fillable fields defined', $body['message']);
    }

    public function test_update_requires_authentication(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(false);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('PUT', '/users/1');
        $response = new Response();

        $result = $controller->update($request, $response, ['id' => 1]);

        $this->assertSame(401, $result->getStatusCode());
    }

    public function test_destroy_requires_authentication(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(false);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('DELETE', '/users/1');
        $response = new Response();

        $result = $controller->destroy($request, $response, ['id' => 1]);

        $this->assertSame(401, $result->getStatusCode());
    }

    public function test_index_requires_authentication(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(false);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('GET', '/users');
        $response = new Response();

        $result = $controller->index($request, $response);

        $this->assertSame(401, $result->getStatusCode());
    }

    public function test_show_requires_authentication(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(false);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('GET', '/users/1');
        $response = new Response();

        $result = $controller->show($request, $response, ['id' => 1]);

        $this->assertSame(401, $result->getStatusCode());
    }

    public function test_store_returns_422_when_no_valid_data_after_filtering(): void
    {
        $user = $this->createUser(['name' => 'Admin', 'email' => 'admin@example.com']);
        $this->simulateLogin($user, ['admin'], ['users.create']);

        $auth = $this->createMock(Auth::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($user);
        $this->container->set(Auth::class, $auth);

        $controller = new TestUserCrudController($this->container);
        $request = $this->requestFactory->createServerRequest('POST', '/users')
            ->withParsedBody([
                'is_admin' => true,
                'hacker_field' => 'bad',
            ]);
        $response = new Response();

        $result = $controller->store($request, $response);

        $this->assertSame(422, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertSame('No valid data provided for creation', $body['message']);
    }

    private function simulateLogin(User $user, array $roles = [], array $permissions = []): void
    {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_roles'] = $roles;
        $_SESSION['user_permissions'] = $permissions;
    }
}
