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
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
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

    public function index(Request $request, Response $response): Response
    {
        $roles = $this->listAction->execute();

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $roles,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(CreateRoleRequest $request, Response $response): Response
    {
        $role = $this->createAction->execute(
            CreateRoleDTO::fromRequest($request->validated())
        );

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $role,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $role = $this->getAction->execute($this->getParamAsInt($args, 'id'));

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $role,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(UpdateRoleRequest $request, Response $response, array $args): Response
    {
        $role = $this->updateAction->execute(
            UpdateRoleDTO::fromRequest($request->validated())
        );

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $role,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteAction->execute($this->getParamAsInt($args, 'id'));

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Role deleted successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
