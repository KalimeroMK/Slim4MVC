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
use App\Traits\ValidatesRequests;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PermissionController extends Controller
{
    use RouteParamsTrait;
    use ValidatesRequests;

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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function store(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, CreatePermissionRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, CreatePermissionRequest::class);
        $dto = new CreatePermissionDTO($validated['name']);

        $permission = $this->createAction->execute($dto);

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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        if (($errorResponse = $this->validateRequest($request, UpdatePermissionRequest::class,
            true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, UpdatePermissionRequest::class);
        $dto = new UpdatePermissionDTO($this->getParamAsInt($args, 'id'), $validated['name']);

        $permission = $this->updateAction->execute($dto);

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
