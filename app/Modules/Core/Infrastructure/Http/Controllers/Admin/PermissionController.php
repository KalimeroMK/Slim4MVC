<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers\Admin;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Infrastructure\Models\Role;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class PermissionController extends Controller
{
    /**
     * Display all permissions.
     */
    public function index(Request $request, Response $response): Response
    {
        $permissions = Permission::with('roles')->get();
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
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['name'])) {
            throw new RuntimeException('Permission name is required');
        }

        // Check if permission exists
        if (Permission::where('name', $data['name'])->exists()) {
            throw new RuntimeException('Permission already exists');
        }

        $permission = Permission::create([
            'name' => $data['name'],
        ]);

        // Attach roles if provided
        if (! empty($data['roles'])) {
            $permission->roles()->sync($data['roles']);
        }

        return $this->redirect('/admin/permissions');
    }

    /**
     * Show edit permission form.
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        $permission = Permission::with('roles')->findOrFail($id);
        $roles = Role::all();

        return view('admin.permissions.edit', $response, [
            'permission' => $permission,
            'roles' => $roles,
        ]);
    }

    /**
     * Update permission.
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        $data = $request->getParsedBody();
        $permission = Permission::findOrFail($id);

        // Update name
        if (! empty($data['name']) && $data['name'] !== $permission->name) {
            // Check if new name already exists
            if (Permission::where('name', $data['name'])->where('id', '!=', $id)->exists()) {
                throw new RuntimeException('Permission name already taken');
            }
            $permission->name = $data['name'];
            $permission->save();
        }

        // Sync roles
        $permission->roles()->sync($data['roles'] ?? []);

        return $this->redirect('/admin/permissions');
    }

    /**
     * Delete permission.
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return $this->redirect('/admin/permissions');
    }
}
