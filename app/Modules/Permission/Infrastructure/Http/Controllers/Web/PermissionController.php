<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Http\Controllers\Web;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\Route;
use App\Modules\Permission\Application\Actions\CreatePermissionAction;
use App\Modules\Permission\Application\Actions\DeletePermissionAction;
use App\Modules\Permission\Application\Actions\GetPermissionAction;
use App\Modules\Permission\Application\Actions\ListPermissionAction;
use App\Modules\Permission\Application\Actions\UpdatePermissionAction;
use App\Modules\Permission\Application\DTOs\CreatePermissionDTO;
use App\Modules\Permission\Application\DTOs\UpdatePermissionDTO;
use App\Modules\Permission\Infrastructure\Http\Requests\Web\CreatePermissionRequest;
use App\Modules\Permission\Infrastructure\Http\Requests\Web\UpdatePermissionRequest;
use App\Modules\Role\Infrastructure\Models\Role;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PermissionController extends Controller
{
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
     * Display all permissions.
     */
    public function index(Request $request, Response $response): Response
    {
        $permissions = $this->listPermissionAction->execute(1, 100)['items'];
        $roles = Role::all();

        return view('admin.permissions.index', $response, [
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }

    /**
     * Show create permission form.
     */
    public function create(Request $request, Response $response): Response
    {
        $roles = Role::all();

        return view('admin.permissions.create', $response, [
            'roles' => $roles,
        ]);
    }

    /**
     * Store new permission.
     */
    public function store(CreatePermissionRequest $request, Response $response): Response
    {
        $this->createPermissionAction->execute(
            CreatePermissionDTO::fromRequest($request->validated())
        );

        return $this->redirect(Route::url('admin.permissions.index'));
    }

    /**
     * Show edit permission form.
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        $permission = $this->getPermissionAction->execute($id);
        $roles = Role::all();

        return view('admin.permissions.edit', $response, [
            'permission' => $permission,
            'roles' => $roles,
        ]);
    }

    /**
     * Update permission.
     */
    public function update(UpdatePermissionRequest $request, Response $response, int $id): Response
    {
        $this->updatePermissionAction->execute(
            UpdatePermissionDTO::fromRequest($id, $request->validated())
        );

        return $this->redirect(Route::url('admin.permissions.index'));
    }

    /**
     * Delete permission.
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        $this->deletePermissionAction->execute($id);

        return $this->redirect(Route::url('admin.permissions.index'));
    }
}
