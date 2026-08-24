<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Slim4MVC')</title>

    {{-- Served from here, not a CDN: the Content-Security-Policy is default-src 'self',
         so the cdn.jsdelivr.net links these replace were blocked and every icon on the
         page came out blank. app.css is itself Bootstrap - 5.0.2, which is the version
         that has actually been in effect all along, the blocked link having claimed
         5.3.2. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
    
    @stack('styles')
</head>
<body>
<div id="app">
    <div id="content">
        @yield('content')
    </div>
</div>

<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

{{-- js/main.js used to be loaded here. No such file has ever existed, so every page
     using this layout asked for it and got a 404. A page that wants its own script
     pushes onto @stack('scripts') below. --}}

@stack('scripts')
</body>
</html>
