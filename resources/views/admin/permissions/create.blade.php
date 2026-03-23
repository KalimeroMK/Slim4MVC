@extends('layouts.admin')

@section('title', 'Create Permission')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="bi bi-key me-2"></i>Create New Permission</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/admin/permissions">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Permission Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" required
                                   placeholder="e.g., create-users, edit-posts, delete-comments">
                            <div class="form-text">Use kebab-case format (e.g., create-users, view-reports).</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Assign to Roles</label>
                            <div class="card">
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    @forelse($roles as $role)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               name="roles[]" value="{{ $role->id }}" 
                                               id="role_{{ $role->id }}">
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            <span class="badge bg-primary">{{ $role->name }}</span>
                                        </label>
                                    </div>
                                    @empty
                                    <p class="text-muted mb-0">No roles available. Create some first.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-1"></i>Create Permission
                            </button>
                            <a href="/admin/permissions" class="btn btn-outline-secondary">
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
