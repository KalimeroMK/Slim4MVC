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

    public function store(CreateRoleRequest $createRoleRequest, Response $response): Response
    {
        $role = $this->createRoleAction->execute(
            CreateRoleDTO::fromRequest($createRoleRequest->validated())
        );

        return ApiResponse::success(RoleResource::make($role), HttpStatusCode::CREATED);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $role = $this->getRoleAction->execute($this->getParamAsInt($args, 'id'));

        return ApiResponse::success(RoleResource::make($role));
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function update(UpdateRoleRequest $updateRoleRequest, Response $response, array $args): Response
    {
        $role = $this->updateRoleAction->execute(
            UpdateRoleDTO::fromRequest($this->getParamAsInt($args, 'id'), $updateRoleRequest->validated())
        );

        return ApiResponse::success(RoleResource::make($role));
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deleteRoleAction->execute($this->getParamAsInt($args, 'id'));

        return ApiResponse::success(null, HttpStatusCode::NO_CONTENT);
    }
}
