<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Role\CreateRoleAction;
use App\Actions\Role\DeleteRoleAction;
use App\Actions\Role\GetRoleAction;
use App\Actions\Role\ListRolesAction;
use App\Actions\Role\UpdateRolePermissionAction;
use App\DTO\Role\CreateRoleDTO;
use App\DTO\Role\UpdateRoleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\CreatePermissionRequest;
use App\Http\Requests\Role\UpdatePermissionRequest;
use App\Trait\ValidatesRequests;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RoleController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        ContainerInterface $container,
        private readonly CreateRoleAction $createRoleAction,
        private readonly UpdateRolePermissionAction $updateRoleAction,
        private readonly DeleteRoleAction $deleteRoleAction,
        private readonly GetRoleAction $getRoleAction,
        private readonly ListRolesAction $listRolesAction
    ) {
        parent::__construct($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request, Response $response): Response
    {
        $roles = $this->listRolesAction->execute();

        return $response->withJson([
            'status' => 'success',
            'data' => $roles,
        ]);
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
        $dto = new CreateRoleDTO(
            $validated['name'],
        );

        $role = $this->createRoleAction->execute($dto);

        return $response->withJson([
            'status' => 'success',
            'data' => $role,
        ], 201);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function show(Request $request, Response $response, int $id): Response
    {
        $role = $this->getRoleAction->execute($id);

        return $response->withJson([
            'status' => 'success',
            'data' => $role,
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        if (($errorResponse = $this->validateRequest($request, UpdatePermissionRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, UpdatePermissionRequest::class);
        $dto = new UpdateRoleDTO(
            $id,
            $validated['name'] ?? null,
        );

        $role = $this->updateRoleAction->execute($dto);

        return $response->withJson([
            'status' => 'success',
            'data' => $role,
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function destroy(Request $request, Response $response, int $id): Response
    {
        $this->deleteRoleAction->execute($id);

        return $response->withJson([
            'status' => 'success',
            'message' => 'Role deleted successfully',
        ]);
    }
}