<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Controllers;

use App\Modules\Core\Application\Enums\HttpStatusCode;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Core\Infrastructure\Traits\RouteParamsTrait;
use App\Modules\Role\Application\Actions\CreateRoleAction;
use App\Modules\Role\Application\Actions\DeleteRoleAction;
use App\Modules\Role\Application\Actions\GetRoleAction;
use App\Modules\Role\Application\Actions\ListRolesAction;
use App\Modules\Role\Application\Actions\UpdateRoleAction;
use App\Modules\Role\Application\DTOs\CreateRoleDTO;
use App\Modules\Role\Application\DTOs\UpdateRoleDTO;
use App\Modules\Role\Infrastructure\Http\Requests\CreateRoleRequest;
use App\Modules\Role\Infrastructure\Http\Requests\UpdateRoleRequest;
use App\Modules\Role\Infrastructure\Http\Resources\RoleResource;
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
        $role = $roleData instanceof \App\Modules\Role\Infrastructure\Models\Role
            ? $roleData
            : \App\Modules\Role\Infrastructure\Models\Role::with('permissions')->find($roleData['id'] ?? null);

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
        $role = \App\Modules\Role\Infrastructure\Models\Role::with('permissions')->find($args['id']);

        return ApiResponse::success(RoleResource::make($role));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteAction->execute($this->getParamAsInt($args, 'id'));

        return ApiResponse::success(null, HttpStatusCode::NO_CONTENT, 'Role deleted successfully');
    }
}
