<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Controllers;

use App\Modules\Core\Application\Enums\HttpStatusCode;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Core\Infrastructure\Traits\RouteParamsTrait;
use App\Modules\User\Application\Actions\CreateUserAction;
use App\Modules\User\Application\Actions\DeleteUserAction;
use App\Modules\User\Application\Actions\GetUserAction;
use App\Modules\User\Application\Actions\ListUsersAction;
use App\Modules\User\Application\Actions\UpdateUserAction;
use App\Modules\User\Application\DTOs\CreateUserDTO;
use App\Modules\User\Application\DTOs\UpdateUserDTO;
use App\Modules\User\Infrastructure\Http\Requests\CreateUserRequest;
use App\Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use App\Modules\User\Infrastructure\Http\Resources\UserResource;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

#[OA\Tag(name: "Users", description: "User management (Authentication required)")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class UserController extends Controller
{
    use RouteParamsTrait;

    public function __construct(
        ContainerInterface $container,
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly DeleteUserAction $deleteUserAction,
        private readonly GetUserAction $getUserAction,
        private readonly ListUsersAction $listUsersAction
    ) {
        parent::__construct($container);
    }

    #[OA\Get(
        path: "/users",
        operationId: "listUsers",
        description: "Get paginated list of all users",
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", in: "query", schema: new OA\Schema(type: "integer", default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of users"),
            new OA\Response(response: 401, description: "Unauthorized"),
        ]
    )]
    public function index(Request $request, Response $response): Response
    {
        $params = $this->getPaginationParams();
        $result = $this->listUsersAction->execute($params['page'], $params['perPage']);

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

    #[OA\Post(
        path: "/users",
        operationId: "createUser",
        description: "Create a new user account",
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "Jane Smith"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "jane@example.com"),
                    new OA\Property(property: "password", type: "string", minLength: 8, example: "securepassword123"),
                    new OA\Property(property: "password_confirmation", type: "string", example: "securepassword123"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "User created"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(CreateUserRequest $createUserRequest, Response $response): Response
    {
        $user = $this->createUserAction->execute(
            CreateUserDTO::fromRequest($createUserRequest->validated())
        );

        // Load relationships for resource
        $user->load('roles');

        return ApiResponse::success(UserResource::make($user), HttpStatusCode::CREATED);
    }

    #[OA\Get(
        path: "/users/{id}",
        operationId: "getUser",
        description: "Retrieve a single user by ID",
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "User details"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "User not found"),
        ]
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $this->getUserAction->execute($args['id']);

        return ApiResponse::success(UserResource::make($user));
    }

    #[OA\Put(
        path: "/users/{id}",
        operationId: "updateUser",
        description: "Full update of user",
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", nullable: true, maxLength: 255, example: "Jane Smith Updated"),
                    new OA\Property(property: "email", type: "string", nullable: true, format: "email", example: "jane.updated@example.com"),
                    new OA\Property(property: "password", type: "string", nullable: true, minLength: 8),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "User updated"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "User not found"),
        ]
    )]
    public function update(UpdateUserRequest $updateUserRequest, Response $response, array $args): Response
    {
        $user = $this->updateUserAction->execute(
            new UpdateUserDTO(
                $args['id'],
                $updateUserRequest->validated()['name'] ?? null,
                $updateUserRequest->validated()['email'] ?? null,
                $updateUserRequest->validated()['password'] ?? null,
                $updateUserRequest->validated()['roles'] ?? []
            )
        );

        // Load relationships for resource
        $user->load('roles');

        return ApiResponse::success(UserResource::make($user));
    }

    #[OA\Delete(
        path: "/users/{id}",
        operationId: "deleteUser",
        description: "Delete a user by ID",
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 204, description: "User deleted"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "User not found"),
        ]
    )]
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteUserAction->execute($args['id']);

        return ApiResponse::success(null, HttpStatusCode::NO_CONTENT, 'User deleted successfully');
    }
}
