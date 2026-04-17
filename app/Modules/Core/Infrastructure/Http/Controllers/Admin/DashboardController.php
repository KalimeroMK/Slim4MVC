<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers\Admin;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    /**
     * Number of items per page for dashboard tables.
     */
    private const PER_PAGE = 10;

    /**
     * Display the admin dashboard.
     */
    public function dashboard(Request $request, Response $response): Response
    {
        // Require admin role for dashboard access
        if (! $this->hasRole('admin')) {
            return ApiResponse::forbidden('Admin access required');
        }

        // Load paginated data for the dashboard to prevent memory issues
        $users = User::with('roles')->paginate(self::PER_PAGE);
        $roles = Role::with(['permissions'])->paginate(self::PER_PAGE);
        $permissions = Permission::with('roles')->paginate(self::PER_PAGE);

        return view('admin.dashboard', $response, [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
