@php
$permissionRoleIds = $permission->roles->pluck('id')->toArray();
@endphp

@extends('layouts.admin')

@section('title', 'Edit Permission')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="bi bi-key me-2"></i>Edit Permission: {{ $permission->name }}</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/admin/permissions/{{ $permission->id }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Permission Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                   value="{{ $permission->name }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Assign to Roles</label>
                            <div class="card">
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    @forelse($roles as $role)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               name="roles[]" value="{{ $role->id }}" 
                                               id="role_{{ $role->id }}"
                                               {{ in_array($role->id, $permissionRoleIds) ? 'checked' : '' }}>
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
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-1"></i>Update Permission
                            </button>
                            <a href="/admin/permissions" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card shadow-sm mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted">Deleting this permission will remove it from all roles. This action cannot be undone.</p>
                    <form method="POST" action="/admin/permissions/{{ $permission->id }}/delete" onsubmit="return confirm('Are you sure you want to delete this permission? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Delete Permission
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
