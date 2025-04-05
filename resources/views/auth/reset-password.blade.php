@extends('layouts.app')

@section('content')
    <div class="container" style="margin-top: 10%">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-md-9 col-lg-6 col-xl-5">
                <img src="{{ asset('img/slim.png') }}" class="img-fluid"
                     alt="Sample image">
            </div>
            @include('partials.error')
            <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
                <form method="POST" action="/reset-password">
                    @csrf
                    <!-- Email input -->
                    <div class="form-outline mb-4">
                        <input type="password" name="password" id="form3Example3" class="form-control form-control-lg"
                               placeholder="Enter new password" /><br>
                        <input type="password" name="password_confirmation" id="form3Example3" class="form-control form-control-lg"
                               placeholder="Confirm new password" />
                        <input type="hidden" name="token" value="{{ $token }}" />
                    </div>


                    <div class="text-center text-lg-start mt-4 pt-2">
                        <button type="submit" class="btn btn-primary btn-lg"
                                style="padding-left: 2.5rem; padding-right: 2.5rem;">Reset</button>

                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
