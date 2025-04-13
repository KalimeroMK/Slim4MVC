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
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\CreatePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
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

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $permissions,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(CreatePermissionRequest $request, Response $response): Response
    {
        $permission = $this->createAction->execute(
            new CreatePermissionDTO($request->validated()['name'])
        );

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $permission,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $permission = $this->getAction->execute($this->getParamAsInt($args, 'id'));

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $permission,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(UpdatePermissionRequest $request, Response $response, array $args): Response
    {
        $permission = $this->updateAction->execute(
            new UpdatePermissionDTO(
                $this->getParamAsInt($args, 'id'),
                $request->validated()['name']
            )
        );

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $permission,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteAction->execute($this->getParamAsInt($args, 'id'));

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Permission deleted successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
