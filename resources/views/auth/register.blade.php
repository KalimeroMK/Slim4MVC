@extends('layouts.app')

@section('content')
    <div class="container" style="margin-top: 10%">
        <div class="row d-flex justify-content-center align-items-center h-100">
            @include('partials.error')
            <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
                <form method="POST" action="/register">
                    @csrf
                    <!-- Email input -->
                    <div class="form-floating">
                        <input class="form-control" id="name" type="text" name="name" placeholder="Name" required />
                        <label for="name">Name</label>
                    </div><br>
                    <div class="form-floating">
                        <input class="form-control" id="email" type="email" name="email" placeholder="Email" required />
                        <label for="email">Email</label>
                    </div><br>
                    <div class="form-floating">
                        <input class="form-control" id="password" type="password" name="password" placeholder="Password" required />
                        <label for="password">Password</label>
                    </div><br>
                    <div class="form-floating">
                        <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm Password" required />
                        <label for="confirm_password">Confirm Password</label>
                    </div>
                    <br>
                    <button class="w-100 btn btn-lg btn-primary" type="submit">Register</button>

                </form>
            </div>
            <div class="col-md-9 col-lg-6 col-xl-5">
                <img src="{{ asset('img/slim.png') }}" class="img-fluid"
                     alt="Sample image">
            </div>
        </div>
    </div>

@endsection
