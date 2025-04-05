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
                <form method="POST" action="/forgot-password">
                    @csrf
                    <!-- Email input -->
                    <div class="form-outline mb-4">
                        <input type="email" name="email" id="form3Example3" class="form-control form-control-lg"
                               placeholder="Enter a valid email address" />
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
