@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="text-center text-black-50">
            <h1 class="display-4">Welcome to Slim 4 MVC</h1>
            <p class="lead">This is a simple starter kit with Eloquent ORM</p>
            <img src="{{ asset('img/slim.png') }}" alt="logo">
        </div>
    </div>
@endsection