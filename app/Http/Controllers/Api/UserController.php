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
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends Controller
{
    use RouteParamsTrait;

    public function __construct(
        ContainerInterface $container,
        private readonly CreateUserAction $createAction,
        private readonly UpdateUserAction $updateAction,
        private readonly DeleteUserAction $deleteAction,
        private readonly GetUserAction $getAction,
        private readonly ListUsersAction $listAction
    ) {
        parent::__construct($container);
    }

    /**
     * List all users.
     *
     * GET /api/v1/users
     */
    public function index(Request $request, Response $response): Response
    {
        $users = $this->listAction->execute();

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
     */
    public function store(CreateUserRequest $request, Response $response): Response
    {
        $user = $this->createAction->execute(
            new CreateUserDTO(
                $request->validated()['name'],
                $request->validated()['email'],
                $request->validated()['password'],
                $request->validated()['roles'] ?? []
            )
        );

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
        $user = $this->getAction->execute($args['id']);

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
     */
    public function update(UpdateUserRequest $request, Response $response, array $args): Response
    {
        $user = $this->updateAction->execute(
            new UpdateUserDTO(
                $args['id'],
                $request->validated()['name'] ?? null,
                $request->validated()['email'] ?? null,
                $request->validated()['password'] ?? null,
                $request->validated()['roles'] ?? []
            )
        );

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
        $this->deleteAction->execute($args['id']);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
