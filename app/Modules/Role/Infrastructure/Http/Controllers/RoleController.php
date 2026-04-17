<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Controllers;

use App\Modules\Core\Infrastructure\Http\Controllers\Concerns\HandlesCrudResponses;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
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
    use HandlesCrudResponses;
    use RouteParamsTrait;

    protected ?string $resourceClass = RoleResource::class;

    public function __construct(
        ContainerInterface $container,
        private readonly CreateRoleAction $createRoleAction,
        private readonly UpdateRoleAction $updateRoleAction,
        private readonly DeleteRoleAction $deleteRoleAction,
        private readonly GetRoleAction $getRoleAction,
        private readonly ListRolesAction $listRolesAction
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
        $result = $this->listRolesAction->execute($params['page'], $params['perPage']);

        return $this->respondPaginated($result);
    }

    public function store(CreateRoleRequest $request, Response $response): Response
    {
        $role = $this->createRoleAction->execute(
            CreateRoleDTO::fromRequest($request->validated())
        );

        return $this->respondCreated($role);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $role = $this->getRoleAction->execute($this->getParamAsInt($args, 'id'));

        return $this->respondResource($role);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function update(UpdateRoleRequest $request, Response $response, array $args): Response
    {
        $role = $this->updateRoleAction->execute(
            UpdateRoleDTO::fromRequest($this->getParamAsInt($args, 'id'), $request->validated())
        );

        return $this->respondUpdated($role);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteRoleAction->execute($this->getParamAsInt($args, 'id'));

        return $this->respondDeleted();
    }
}
