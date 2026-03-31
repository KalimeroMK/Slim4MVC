<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Http\Controllers;

use App\Modules\Core\Infrastructure\Http\Controllers\Concerns\HandlesCrudResponses;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
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
    use HandlesCrudResponses;

    protected ?string $resourceClass = PermissionResource::class;

    public function __construct(
        ContainerInterface $container,
        private readonly CreatePermissionAction $createPermissionAction,
        private readonly UpdatePermissionAction $updatePermissionAction,
        private readonly DeletePermissionAction $deletePermissionAction,
        private readonly GetPermissionAction $getPermissionAction,
        private readonly ListPermissionAction $listPermissionAction
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
        $result = $this->listPermissionAction->execute($params['page'], $params['perPage']);

        return $this->respondPaginated($result);
    }

    public function store(CreatePermissionRequest $request, Response $response): Response
    {
        $permission = $this->createPermissionAction->execute(
            CreatePermissionDTO::fromRequest($request->validated())
        );

        return $this->respondCreated($permission);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $permission = $this->getPermissionAction->execute($this->getParamAsInt($args, 'id'));

        return $this->respondResource($permission);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function update(UpdatePermissionRequest $request, Response $response, array $args): Response
    {
        $permission = $this->updatePermissionAction->execute(
            UpdatePermissionDTO::fromRequest($this->getParamAsInt($args, 'id'), $request->validated())
        );

        return $this->respondUpdated($permission);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->deletePermissionAction->execute($this->getParamAsInt($args, 'id'));

        return $this->respondDeleted();
    }
}
