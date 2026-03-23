@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center text-center py-5">
            <div class="col-lg-8">
                <h1 class="display-3 fw-bold mb-4">Slim 4 MVC</h1>
                <p class="lead fs-4 text-muted mb-4">
                    A modern, production-ready starter kit for building web applications with Slim Framework 4
                </p>
                <img src="{{ asset('img/slim.png') }}" alt="Slim Logo" class="mb-4" style="max-width: 150px;">
                
                @guest
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-5">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg px-5">Register</a>
                    </div>
                @else
                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg px-5">Go to Dashboard</a>
                    </div>
                @endguest
            </div>
        </div>

        <div class="row py-5">
            <div class="col-12 text-center mb-5">
                <h2 class="fw-bold">What's Included</h2>
                <p class="text-muted">Everything you need to build modern PHP applications</p>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h5 class="card-title">Authentication</h5>
                        <p class="card-text text-muted">JWT-based API auth and session-based web auth with complete login/register system.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h5 class="card-title">Authorization</h5>
                        <p class="card-text text-muted">Role and permission-based access control with middleware and policies.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h5 class="card-title">Eloquent ORM</h5>
                        <p class="card-text text-muted">Laravel's powerful database toolkit with migrations, seeders, and factories.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h5 class="card-title">Blade Templating</h5>
                        <p class="card-text text-muted">Lightweight BladeOne engine with custom directives.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h5 class="card-title">Modular Architecture</h5>
                        <p class="card-text text-muted">Feature-based module organization with CLI scaffolding commands.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <h5 class="card-title">Testing Suite</h5>
                        <p class="card-text text-muted">Comprehensive test coverage with PHPUnit (215+ tests included).</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row py-5">
            <div class="col-12">
                <div class="bg-light rounded-3 p-5 text-center">
                    <h3 class="fw-bold mb-4">Get Started</h3>
                    <div class="row justify-content-center">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('login') }}" class="text-decoration-none">
                                <div class="p-3 border rounded">
                                    <h5>Login</h5>
                                    <p class="small text-muted mb-0">Access your account</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('register') }}" class="text-decoration-none">
                                <div class="p-3 border rounded">
                                    <h5>Register</h5>
                                    <p class="small text-muted mb-0">Create new account</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('api.docs') }}" class="text-decoration-none">
                                <div class="p-3 border rounded bg-white">
                                    <h5>API Docs</h5>
                                    <p class="small text-muted mb-0">Swagger UI Documentation</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row py-4 text-center">
            <div class="col-12">
                <p class="text-muted mb-0">
                    Slim4MVC - Modern PHP Development Made Simple | 
                    <a href="https://github.com/KalimeroMK/Slim4MVC" target="_blank" class="text-decoration-none">GitHub</a>
                </p>
            </div>
        </div>
    </div>
@endsection
