<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - Slim4MVC</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        /* Admin Layout with Sidebar */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Vertical Sidebar */
        .sidebar {
            width: 280px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand h4 {
            margin: 0;
            font-weight: 700;
            color: #fff;
        }

        .sidebar-brand p {
            margin: 0.25rem 0 0 0;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.6);
        }

        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
        }

        .nav-section-title {
            padding: 0.75rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.4);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: #667eea;
        }

        .nav-link i {
            width: 24px;
            margin-right: 0.75rem;
            font-size: 1.125rem;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.7);
            background: rgba(255,255,255,0.05);
            border: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .btn-logout i {
            margin-right: 0.75rem;
        }

        /* User Dropdown in Sidebar */
        .sidebar-user-dropdown {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
        }

        .user-toggle {
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 0.5rem;
            transition: background 0.2s;
        }

        .user-toggle:hover {
            background: rgba(255,255,255,0.1);
        }

        .avatar-sm {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .user-info {
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            color: #fff;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #user-chevron {
            color: rgba(255,255,255,0.5);
            transition: transform 0.3s;
            font-size: 0.75rem;
        }

        #user-chevron.rotate {
            transform: rotate(180deg);
        }

        .user-menu {
            display: none;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-menu.show {
            display: block;
        }

        .user-menu-item {
            display: flex;
            align-items: center;
            padding: 0.625rem 0.75rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 0.375rem;
            transition: all 0.2s;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 0.9rem;
        }

        .user-menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .user-menu-item i {
            width: 20px;
            margin-right: 0.75rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* Stat Cards */
        .stat-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            color: white;
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-icon.roles {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-icon.permissions {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 0.5rem 0;
        }

        .stat-card p {
            margin: 0;
            color: #6c757d;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .page-header p {
            margin: 0.5rem 0 0 0;
            color: #6c757d;
        }

        /* Modern Tables with Rounded Design */
        .content-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }

        .content-card .card-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border-bottom: 1px solid #f0f0f0;
        }

        .content-card .card-header h5 {
            margin: 0;
            font-weight: 700;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .content-card .card-body {
            padding: 0;
        }

        .table-responsive {
            width: 100%;
            padding: 0.5rem;
        }

        .table-dashboard {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .table-dashboard thead th {
            background: transparent;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            border: none;
            padding: 1rem 1.25rem;
        }

        .table-dashboard tbody tr {
            transition: all 0.2s ease;
        }

        .table-dashboard tbody td {
            padding: 1.25rem;
            background: #fff;
            border: none;
            vertical-align: middle;
        }

        .table-dashboard tbody tr:hover td {
            background: #f8f9fa;
        }

        /* Rounded corners for table rows */
        .table-dashboard tbody tr td:first-child {
            border-radius: 1rem 0 0 1rem;
        }

        .table-dashboard tbody tr td:last-child {
            border-radius: 0 1rem 1rem 0;
        }

        .table-dashboard tbody tr:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Avatar styling */
        .table-dashboard .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Modern Badges */
        .badge-role {
            display: inline-inline-flex;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .badge-role:hover {
            transform: translateY(-2px);
        }

        .badge-admin {
            background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
            color: #be185d;
        }

        .badge-user {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #047857;
        }

        .badge-moderator {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #b45309;
        }

        .badge-manager {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
        }
    </style>
    
    @stack('styles')
</head>
<body>
@php
use App\Modules\Core\Infrastructure\Support\AuthHelper;
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
@endphp

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="/dashboard" class="sidebar-brand" style="text-decoration: none;">
            <h4><i class="bi bi-rocket-takeoff me-2"></i>Slim4MVC</h4>
            <p>Admin Dashboard</p>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Main Menu</div>
            <a href="/admin/users" class="nav-link {{ str_contains($currentPath, '/admin/users') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>Manage Users</span>
            </a>
            <a href="/admin/roles" class="nav-link {{ str_contains($currentPath, '/admin/roles') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i>
                <span>Manage Roles</span>
            </a>
            <a href="/admin/permissions" class="nav-link {{ str_contains($currentPath, '/admin/permissions') ? 'active' : '' }}">
                <i class="bi bi-key"></i>
                <span>Manage Permissions</span>
            </a>

            <div class="nav-section-title">System</div>
            <a href="/api-docs" class="nav-link" target="_blank">
                <i class="bi bi-book"></i>
                <span>API Docs</span>
            </a>
        </nav>

        <!-- User Dropdown -->
        <div class="sidebar-user-dropdown">
            <div class="user-toggle" onclick="toggleUserMenu()">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm">{{ substr(AuthHelper::user()['name'] ?? 'U', 0, 1) }}</div>
                    <div class="user-info ms-2">
                        <div class="user-name">{{ AuthHelper::user()['name'] ?? 'User' }}</div>
                        <div class="user-email">{{ AuthHelper::user()['email'] ?? '' }}</div>
                    </div>
                    <i class="bi bi-chevron-up ms-auto" id="user-chevron"></i>
                </div>
            </div>
            <div class="user-menu" id="user-menu">
                <a href="/profile" class="user-menu-item">
                    <i class="bi bi-person"></i>
                    <span>Profile</span>
                </a>
                <form method="POST" action="/logout" style="margin: 0;">
                    @csrf
                    <button type="submit" class="user-menu-item">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleUserMenu() {
    const menu = document.getElementById('user-menu');
    const chevron = document.getElementById('user-chevron');
    menu.classList.toggle('show');
    chevron.classList.toggle('rotate');
}
</script>

@stack('scripts')
</body>
</html>
