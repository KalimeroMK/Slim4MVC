@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-shield-check me-2"></i>Create New Role</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/admin/roles">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" required
                                   placeholder="e.g., editor, moderator, manager">
                            <div class="form-text">Choose a unique name for this role.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Assign Permissions</label>
                            <div class="card">
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    @forelse($permissions as $permission)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               name="permissions[]" value="{{ $permission->id }}" 
                                               id="perm_{{ $permission->id }}">
                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                    @empty
                                    <p class="text-muted mb-0">No permissions available. Create some first.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Create Role
                            </button>
                            <a href="/admin/roles" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
