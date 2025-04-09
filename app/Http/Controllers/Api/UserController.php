<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\GetUserAction;
use App\Actions\User\ListUsersAction;
use App\Actions\User\UpdateUserAction;
use App\DTO\User\CreateUserDTO;
use App\DTO\User\UpdateUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Traits\RouteParamsTrait;
use App\Traits\ValidatesRequests;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends Controller
{
    use RouteParamsTrait;
    use ValidatesRequests;

    private CreateUserAction $createUserAction;

    private UpdateUserAction $updateUserAction;

    private DeleteUserAction $deleteUserAction;

    private GetUserAction $getUserAction;

    private ListUsersAction $listUsersAction;

    /**
     * UserController constructor.
     */
    public function __construct(
        ContainerInterface $container,
        CreateUserAction $createUserAction,
        UpdateUserAction $updateUserAction,
        DeleteUserAction $deleteUserAction,
        GetUserAction $getUserAction,
        ListUsersAction $listUsersAction
    ) {
        parent::__construct($container);
        $this->createUserAction = $createUserAction;
        $this->updateUserAction = $updateUserAction;
        $this->deleteUserAction = $deleteUserAction;
        $this->getUserAction = $getUserAction;
        $this->listUsersAction = $listUsersAction;
    }

    /**
     * List all users.
     *
     * GET /api/v1/users
     */
    public function index(Request $request, Response $response): Response
    {

        $users = $this->listUsersAction->execute();
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $users,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create a new user.
     *
     * POST /api/v1/users
     *
     * Expected JSON body:
     * {
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "password": "secret"
     * }
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function store(Request $request, Response $response): Response
    {
        // Validate the request and return error response if validation fails.
        if (($errorResponse = $this->validateRequest($request, CreateUserRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        // Retrieve validated data.
        $validated = $this->validatedData($request, CreateUserRequest::class);

        // Create a DTO from the validated data.
        $dto = new CreateUserDTO(
            $validated['name'] ?? '',
            $validated['email'] ?? '',
            $validated['password'] ?? ''
        );

        // Execute the action to create a user.
        $user = $this->createUserAction->execute($dto);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $user,
        ]));

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    /**
     * Retrieve a single user by ID.
     *
     * GET /api/v1/users/{id}
     *
     * @param  array  $args  Array with route parameters (expects 'id')
     */
    public function show(Request $request, Response $response, array $args): Response
    {

        $user = $this->getUserAction->execute($this->getParamAsInt($args, 'id'));

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $user,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update an existing user.
     *
     * PUT /api/v1/users/{id}
     *
     * Expected JSON body:
     * {
     *   "name": "New Name",
     *   "email": "new-email@example.com"
     * }
     *
     * @param  array  $args  Array with route parameters (expects 'id')
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        if (($errorResponse = $this->validateRequest($request, UpdateUserRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, UpdateUserRequest::class);

        $dto = new UpdateUserDTO(
            $this->getParamAsInt($args, 'id'),
            $validated['name'] ?? null,
            $validated['email'] ?? null
        );

        $user = $this->updateUserAction->execute($dto);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $user,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Delete a user by ID.
     *
     * DELETE /api/v1/users/{id}
     *
     * @param  array  $args  Array with route parameters (expects 'id')
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {

        $this->deleteUserAction->execute($this->getParamAsInt($args, 'id'));

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
