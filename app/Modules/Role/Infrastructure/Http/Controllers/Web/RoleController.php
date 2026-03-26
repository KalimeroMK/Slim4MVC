<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Controllers\Web;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Infrastructure\Models\Role;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class RoleController extends Controller
{
    /**
     * Display all roles.
     */
    public function index(Request $request, Response $response): Response
    {
        /** @phpstan-ignore-next-line */
        $roles = Role::with('permissions')->get();
        /** @phpstan-ignore-next-line */
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
        /** @phpstan-ignore-next-line */
        $permissions = Permission::all();

        return view('admin.roles.create', $response, [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store new role.
     */
    public function store(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $data */
        $data = $request->getParsedBody();

        if (empty($data['name'])) {
            throw new RuntimeException('Role name is required');
        }

        /** @phpstan-ignore-next-line */
        if (Role::where('name', $data['name'])->exists()) {
            throw new RuntimeException('Role already exists');
        }

        /** @phpstan-ignore-next-line */
        $role = Role::create([
            'name' => $data['name'],
        ]);

        // Attach permissions if provided
        if (! empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $this->redirect('/admin/roles');
    }

    /**
     * Show edit role form.
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        /** @phpstan-ignore-next-line */
        $role = Role::with('permissions')->findOrFail($id);
        /** @phpstan-ignore-next-line */
        $permissions = Permission::all();

        return view('admin.roles.edit', $response, [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update role.
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        /** @var array<string, mixed> $data */
        $data = $request->getParsedBody();
        /** @phpstan-ignore-next-line */
        $role = Role::findOrFail($id);

        // Update name
        if (! empty($data['name']) && $data['name'] !== $role->name) {
            /** @phpstan-ignore-next-line */
            if (Role::where('name', $data['name'])->where('id', '!=', $id)->exists()) {
                throw new RuntimeException('Role name already taken');
            }
            $role->name = $data['name'];
            $role->save();
        }

        // Sync permissions
        $role->permissions()->sync($data['permissions'] ?? []);

        return $this->redirect('/admin/roles');
    }

    /**
     * Delete role.
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        /** @phpstan-ignore-next-line */
        $role = Role::findOrFail($id);

        /** @phpstan-ignore-next-line */
        if ($role->name === 'admin' && Role::count() === 1) {
            throw new RuntimeException('Cannot delete the only admin role');
        }

        $role->delete();

        return $this->redirect('/admin/roles');
    }
}
