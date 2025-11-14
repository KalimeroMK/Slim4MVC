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
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
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
     * List all users with pagination.
     *
     * GET /api/v1/users?page=1&per_page=15
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $this->getPaginationParams();
        $result = $this->listAction->execute($params['page'], $params['perPage']);

        $items = UserResource::collection($result['items']);

        return ApiResponse::paginated(
            $items,
            $result['total'],
            $result['page'],
            $result['perPage'],
            HttpStatusCode::OK,
            $this->getPaginationBaseUrl()
        );
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
        $userData = $this->createAction->execute(
            CreateUserDTO::fromRequest($request->validated())
        );

        // Reload user with relationships for resource
        $user = \App\Models\User::with('roles')->find($userData['id']);

        return ApiResponse::success(UserResource::make($user), HttpStatusCode::CREATED);
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

        return ApiResponse::success(UserResource::make($user));
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
        $this->updateAction->execute(
            new UpdateUserDTO(
                $args['id'],
                $request->validated()['name'] ?? null,
                $request->validated()['email'] ?? null,
                $request->validated()['password'] ?? null,
                $request->validated()['roles'] ?? []
            )
        );

        // Reload user with relationships for resource
        $user = \App\Models\User::with('roles')->find($args['id']);

        return ApiResponse::success(UserResource::make($user));
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

        return ApiResponse::success(null, HttpStatusCode::NO_CONTENT, 'User deleted successfully');
    }
}
