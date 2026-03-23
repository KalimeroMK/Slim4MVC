@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="bi bi-person-plus me-2"></i>Create New User</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/admin/users">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   placeholder="e.g., John Doe">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   placeholder="e.g., john@example.com">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required
                                       placeholder="Min 6 characters" minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required
                                       placeholder="Repeat password">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Assign Roles</label>
                            <div class="card">
                                <div class="card-body" style="max-height: 200px; overflow-y: auto;">
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
                                    <p class="text-muted mb-0">No roles available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-info text-white">
                                <i class="bi bi-check-lg me-1"></i>Create User
                            </button>
                            <a href="/admin/users" class="btn btn-outline-secondary">
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
