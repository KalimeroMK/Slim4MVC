<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Controllers\Web;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\Route;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Application\Actions\CreateRoleAction;
use App\Modules\Role\Application\Actions\DeleteRoleAction;
use App\Modules\Role\Application\Actions\GetRoleAction;
use App\Modules\Role\Application\Actions\ListRolesAction;
use App\Modules\Role\Application\Actions\UpdateRoleAction;
use App\Modules\Role\Application\DTOs\CreateRoleDTO;
use App\Modules\Role\Application\DTOs\UpdateRoleDTO;
use App\Modules\Role\Infrastructure\Http\Requests\Web\CreateRoleRequest;
use App\Modules\Role\Infrastructure\Http\Requests\Web\UpdateRoleRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RoleController extends Controller
{
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
     * Display all roles.
     */
    public function index(Request $request, Response $response): Response
    {
        $roles = $this->listRolesAction->execute(1, 100)['items'];
        $permissions = Permission::all();

        return view('admin.roles.index', $response, [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Show create role form.
     */
    public function create(Request $request, Response $response): Response
    {
        $permissions = Permission::all();

        return view('admin.roles.create', $response, [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store new role.
     */
    public function store(CreateRoleRequest $request, Response $response): Response
    {
        $this->createRoleAction->execute(
            CreateRoleDTO::fromRequest($request->validated())
        );

        return $this->redirect(Route::url('admin.roles.index'));
    }

    /**
     * Show edit role form.
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        $role = $this->getRoleAction->execute($id);
        $permissions = Permission::all();

        return view('admin.roles.edit', $response, [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update role.
     */
    public function update(UpdateRoleRequest $request, Response $response, int $id): Response
    {
        $this->updateRoleAction->execute(
            UpdateRoleDTO::fromRequest($id, $request->validated())
        );

        return $this->redirect(Route::url('admin.roles.index'));
    }

    /**
     * Delete role.
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        $this->deleteRoleAction->execute($id);

        return $this->redirect(Route::url('admin.roles.index'));
    }
}
