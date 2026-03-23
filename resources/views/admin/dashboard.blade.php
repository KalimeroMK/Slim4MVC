@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
        <!-- Overview Section -->
        <div id="section-overview" class="content-section">
            <div class="page-header">
                <h1>Dashboard Overview</h1>
                <p>Welcome back! Here's what's happening in your system.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3>{{ count($users ?? []) }}</h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon roles">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <h3>{{ count($roles ?? []) }}</h3>
                        <p>Total Roles</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon permissions">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <h3>{{ count($permissions ?? []) }}</h3>
                        <p>Total Permissions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div id="section-users" class="content-section" style="display: none;">
            <div class="page-header">
                <h1>Users</h1>
                <p>Manage system users and their roles.</p>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-people me-2"></i>All Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dashboard">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Roles</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users ?? [] as $user)
                                <tr>
                                    <td><span class="text-muted">#{{ $user->id }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.875rem; font-weight: 600;">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <span class="fw-semibold">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles ?? [] as $role)
                                            <span class="badge-role badge-{{ strtolower($role->name) }}">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-muted">{{ $user->created_at?->format('M d, Y') ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No users found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles Section -->
        <div id="section-roles" class="content-section" style="display: none;">
            <div class="page-header">
                <h1>Roles</h1>
                <p>System roles and their permissions.</p>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-shield-check me-2"></i>All Roles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper">
                        <table class="table table-dashboard">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Permissions</th>
                                    <th>Users Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles ?? [] as $role)
                                <tr>
                                    <td><span class="text-muted">#{{ $role->id }}</span></td>
                                    <td>
                                        <span class="badge-role badge-{{ strtolower($role->name) }}">{{ $role->name }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $role->permissions->pluck('name')->implode(', ') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $role->users->count() }} users</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No roles found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Section -->
        <div id="section-permissions" class="content-section" style="display: none;">
            <div class="page-header">
                <h1>Permissions</h1>
                <p>Available system permissions.</p>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-key me-2"></i>All Permissions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-wrapper">
                        <table class="table table-dashboard">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Assigned Roles</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions ?? [] as $permission)
                                <tr>
                                    <td><span class="text-muted">#{{ $permission->id }}</span></td>
                                    <td class="fw-semibold">{{ $permission->name }}</td>
                                    <td>
                                        @foreach($permission->roles ?? [] as $role)
                                            <span class="badge-role badge-{{ strtolower($role->name) }}">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No permissions found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


<script>
function showSection(sectionName, element) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show selected section
    document.getElementById('section-' + sectionName).style.display = 'block';
    
    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    element.classList.add('active');
}
</script>
@endsection
