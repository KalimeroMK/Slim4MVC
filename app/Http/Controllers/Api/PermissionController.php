<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Permission\CreatePermissionAction;
use App\Actions\Permission\DeletePermissionAction;
use App\Actions\Permission\GetPermissionAction;
use App\Actions\Permission\ListPermissionAction;
use App\Actions\Permission\UpdatePermissionAction;
use App\DTO\Permission\CreatePermissionDTO;
use App\DTO\Permission\UpdatePermissionDTO;
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\CreatePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Support\ApiResponse;
use App\Traits\RouteParamsTrait;
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

    public function index(Request $request, Response $response): Response
    {
        $permissions = $this->listAction->execute();

        return ApiResponse::success(PermissionResource::collection($permissions));
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
