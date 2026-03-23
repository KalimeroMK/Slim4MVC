@php
$userRoleIds = $user->roles->pluck('id')->toArray();
@endphp

@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Edit User Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="bi bi-person-gear me-2"></i>Edit User: {{ $user->name }}</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/admin/users/{{ $user->id }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="{{ $user->name }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="{{ $user->email }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Assign Roles</label>
                            <div class="card">
                                <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                                    @forelse($roles as $role)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               name="roles[]" value="{{ $role->id }}" 
                                               id="role_{{ $role->id }}"
                                               {{ in_array($role->id, $userRoleIds) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            <span class="badge bg-primary">{{ $role->name }}</span>
                                        </label>
                                    </div>
                                    @empty
                                    <p class="text-muted mb-0">No roles available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-info text-white">
                                <i class="bi bi-check-lg me-1"></i>Update User
                            </button>
                            <a href="/admin/users" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-key me-2"></i>Change Password</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/admin/users/{{ $user->id }}/password">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required
                                       placeholder="Min 6 characters" minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required
                                       placeholder="Repeat password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-1"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted">Deleting this user will remove all their data. This action cannot be undone.</p>
                    <form method="POST" action="/admin/users/{{ $user->id }}/delete" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
