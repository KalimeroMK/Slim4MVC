<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Http\Controllers;

use App\Modules\Core\Application\Enums\HttpStatusCode;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Core\Infrastructure\Traits\RouteParamsTrait;
use App\Modules\Permission\Application\Actions\CreatePermissionAction;
use App\Modules\Permission\Application\Actions\DeletePermissionAction;
use App\Modules\Permission\Application\Actions\GetPermissionAction;
use App\Modules\Permission\Application\Actions\ListPermissionAction;
use App\Modules\Permission\Application\Actions\UpdatePermissionAction;
use App\Modules\Permission\Application\DTOs\CreatePermissionDTO;
use App\Modules\Permission\Application\DTOs\UpdatePermissionDTO;
use App\Modules\Permission\Infrastructure\Http\Requests\CreatePermissionRequest;
use App\Modules\Permission\Infrastructure\Http\Requests\UpdatePermissionRequest;
use App\Modules\Permission\Infrastructure\Http\Resources\PermissionResource;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PermissionController extends Controller
{
    use RouteParamsTrait;

    public function __construct(
        ContainerInterface $container,
        private readonly CreatePermissionAction $createAction,
        private readonly UpdatePermissionAction $updateAction,
        private readonly DeletePermissionAction $deleteAction,
        private readonly GetPermissionAction $getAction,
        private readonly ListPermissionAction $listAction,
        private readonly GetPermissionAction $rolesAction
    ) {
        parent::__construct($container);
    }

    /**
     * List all permissions with pagination.
     *
     * GET /api/v1/permissions?page=1&per_page=15
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $this->getPaginationParams();
        $result = $this->listAction->execute($params['page'], $params['perPage']);

        $items = PermissionResource::collection($result['items']);

        return ApiResponse::paginated(
            $items,
            $result['total'],
            $result['page'],
            $result['perPage'],
            HttpStatusCode::OK,
            $this->getPaginationBaseUrl()
        );
    }

    public function store(CreatePermissionRequest $request, Response $response): Response
    {
        $permission = $this->createAction->execute(
            CreatePermissionDTO::fromRequest($request->validated())
        );

        // Load relationships for resource
        $permission->load('roles');

        return ApiResponse::success(PermissionResource::make($permission), HttpStatusCode::CREATED);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $permission = $this->getAction->execute($this->getParamAsInt($args, 'id'));

        return ApiResponse::success(PermissionResource::make($permission));
    }

    public function update(UpdatePermissionRequest $request, Response $response, array $args): Response
    {
        $permission = $this->updateAction->execute(
            UpdatePermissionDTO::fromRequest($request->validated())
        );

        // Load relationships for resource
        $permission->load('roles');

        return ApiResponse::success(PermissionResource::make($permission));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteAction->execute($this->getParamAsInt($args, 'id'));

        return ApiResponse::success(null, HttpStatusCode::NO_CONTENT, 'Permission deleted successfully');
    }
}
