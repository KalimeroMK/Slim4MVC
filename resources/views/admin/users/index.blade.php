@extends('layouts.admin')

@section('title', 'Manage Users')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-people me-2"></i>Manage Users</h2>
            <p class="text-muted mb-0">Manage system users, roles and passwords</p>
        </div>
        <a href="/admin/users/create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Create User
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">User</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Created</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                         style="width: 40px; height: 40px; font-size: 1rem; font-weight: 600;">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <span class="fw-semibold">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->roles->count() > 0)
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-primary me-1">{{ $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="badge bg-secondary">No roles</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $user->created_at?->format('M d, Y') ?? '-' }}</td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="/admin/users/{{ $user->id }}/edit" class="btn btn-sm btn-outline-primary" title="Edit User">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="/admin/users/{{ $user->id }}/password" class="btn btn-sm btn-outline-warning" title="Change Password">
                                        <i class="bi bi-key"></i>
                                    </a>
                                    <form method="POST" action="/admin/users/{{ $user->id }}/delete" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No users found
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
