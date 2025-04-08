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
use App\Http\Requests\Role\CreatePermissionRequest;
use App\Http\Requests\Role\UpdatePermissionRequest;
use App\Trait\ValidatesRequests;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PermissionController extends Controller
{
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
        return $response->withJson(['data' => $permissions]);
    }

    public function store(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, CreatePermissionRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, CreatePermissionRequest::class);
        $dto = new CreatePermissionDTO($validated['name'], $validated['description'] ?? null);

        $permission = $this->createAction->execute($dto);
        return $response->withJson(['data' => $permission], 201);
    }

    public function show(Request $request, Response $response, int $id): Response
    {
        $permission = $this->getAction->execute($id);
        return $response->withJson(['data' => $permission]);
    }

    public function update(Request $request, Response $response, int $id): Response
    {
        if (($errorResponse = $this->validateRequest($request, UpdatePermissionRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, UpdatePermissionRequest::class);
        $dto = new UpdatePermissionDTO($id, $validated['name'] ?? null, $validated['description'] ?? null);

        $permission = $this->updateAction->execute($dto);
        return $response->withJson(['data' => $permission]);
    }

    public function destroy(Request $request, Response $response, int $id): Response
    {
        $this->deleteAction->execute($id);
        return $response->withJson(['message' => 'Permission deleted successfully']);
    }

    public function roles(Request $request, Response $response, int $id): Response
    {
        $roles = $this->rolesAction->execute($id);
        return $response->withJson(['data' => $roles]);
    }
}