@extends('layouts.admin')

@section('title', 'Manage Roles')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-shield-check me-2"></i>Manage Roles</h2>
            <p class="text-muted mb-0">Manage system roles and their permissions</p>
        </div>
        <a href="/admin/roles/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Create Role
        </a>
    </div>

    @if(!empty($_SESSION['success']))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ $_SESSION['success'] }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @php unset($_SESSION['success']); @endphp
    @endif

    @if(!empty($_SESSION['error']))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $_SESSION['error'] }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @php unset($_SESSION['error']); @endphp
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">ID</th>
                            <th>Name</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                        <tr>
                            <td class="px-4 text-muted">#{{ $role->id }}</td>
                            <td>
                                <span class="badge bg-primary fs-6">{{ $role->name }}</span>
                            </td>
                            <td>
                                @if($role->permissions->count() > 0)
                                    @foreach($role->permissions as $permission)
                                        <span class="badge bg-secondary me-1">{{ $permission->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted fst-italic">No permissions</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $role->users->count() }} users</span>
                            </td>
                            <td class="text-end px-4">
                                <a href="/admin/roles/{{ $role->id }}/edit" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/admin/roles/{{ $role->id }}/delete" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No roles found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>
@endsection
