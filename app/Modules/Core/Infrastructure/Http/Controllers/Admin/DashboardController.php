<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers\Admin;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function dashboard(Request $request, Response $response): Response
    {
        // Load data for the dashboard
        $users = User::with('roles')->get();
        $roles = Role::with(['permissions', 'users'])->get();
        $permissions = Permission::with('roles')->get();

        return view('admin.dashboard', $response, [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
