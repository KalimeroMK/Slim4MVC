@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Registration</h1>
        <div class="form-signup">
            @if(isset($errors))
                @foreach($errors->all() as $error)
                    <div class="alert alert-danger text-center" role="alert">
                        {{ $error }}
                    </div>
                @endforeach
            @endif
            <form method="POST" action="/register">
                @csrf
                <div class="form-floating">
                    <input class="form-control" id="name" type="text" name="name" placeholder="Name" required />
                    <label for="name">Name</label>
                </div>
                <div class="form-floating">
                    <input class="form-control" id="email" type="email" name="email" placeholder="Email" required />
                    <label for="email">Email</label>
                </div>
                <div class="form-floating">
                    <input class="form-control" id="password" type="password" name="password" placeholder="Password" required />
                    <label for="password">Password</label>
                </div>
                <div class="form-floating">
                    <input class="form-control" id="confirm_password" type="password" name="confirm_password" placeholder="Confirm Password" required />
                    <label for="confirm_password">Confirm Password</label>
                </div>
                <button class="w-100 btn btn-lg btn-primary" type="submit">Register</button>
            </form>
        </div>
    </div>
@endsection
