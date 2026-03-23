@extends('layouts.admin')

@section('title', 'Manage Permissions')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-key me-2"></i>Manage Permissions</h2>
            <p class="text-muted mb-0">Manage system permissions and their assignments</p>
        </div>
        <a href="/admin/permissions/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Create Permission
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">ID</th>
                            <th>Name</th>
                            <th>Assigned Roles</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permissions as $permission)
                        <tr>
                            <td class="px-4 text-muted">#{{ $permission->id }}</td>
                            <td>
                                <span class="badge bg-secondary fs-6">{{ $permission->name }}</span>
                            </td>
                            <td>
                                @if($permission->roles->count() > 0)
                                    @foreach($permission->roles as $role)
                                        <span class="badge bg-primary me-1">{{ $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted fst-italic">Not assigned</span>
                                @endif
                            </td>
                            <td class="text-end px-4">
                                <a href="/admin/permissions/{{ $permission->id }}/edit" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="/admin/permissions/{{ $permission->id }}/delete" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this permission?');">
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
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No permissions found
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
