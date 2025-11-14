<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Role\CreateRoleAction;
use App\Actions\Role\DeleteRoleAction;
use App\Actions\Role\GetRoleAction;
use App\Actions\Role\ListRolesAction;
use App\Actions\Role\UpdateRoleAction;
use App\DTO\Role\CreateRoleDTO;
use App\DTO\Role\UpdateRoleDTO;
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Support\ApiResponse;
use App\Traits\RouteParamsTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RoleController extends Controller
{
    use RouteParamsTrait;

    public function __construct(
        ContainerInterface $container,
        private readonly CreateRoleAction $createAction,
        private readonly UpdateRoleAction $updateAction,
        private readonly DeleteRoleAction $deleteAction,
        private readonly GetRoleAction $getAction,
        private readonly ListRolesAction $listAction
    ) {
        parent::__construct($container);
    }

    /**
     * List all roles with pagination.
     *
     * GET /api/v1/roles?page=1&per_page=15
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $this->getPaginationParams();
        $result = $this->listAction->execute($params['page'], $params['perPage']);

        $items = RoleResource::collection($result['items']);

        return ApiResponse::paginated(
            $items,
            $result['total'],
            $result['page'],
            $result['perPage'],
            HttpStatusCode::OK,
            $this->getPaginationBaseUrl()
        );
    }

    public function store(CreateRoleRequest $request, Response $response): Response
    {
        $roleData = $this->createAction->execute(
            CreateRoleDTO::fromRequest($request->validated())
        );

        // CreateRoleAction returns Role model (load() returns the model, not array)
        $role = $roleData instanceof \App\Models\Role 
            ? $roleData 
            : \App\Models\Role::with('permissions')->find($roleData['id'] ?? null);

        return ApiResponse::success(RoleResource::make($role), HttpStatusCode::CREATED);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $role = $this->getAction->execute($this->getParamAsInt($args, 'id'));

        return ApiResponse::success(RoleResource::make($role));
    }

    public function update(UpdateRoleRequest $request, Response $response, array $args): Response
    {
        $this->updateAction->execute(
            UpdateRoleDTO::fromRequest($request->validated())
        );

        // Reload role with relationships for resource
        $role = \App\Models\Role::with('permissions')->find($args['id']);

        return ApiResponse::success(RoleResource::make($role));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteAction->execute($this->getParamAsInt($args, 'id'));

        return ApiResponse::success(null, HttpStatusCode::NO_CONTENT, 'Role deleted successfully');
    }
}
