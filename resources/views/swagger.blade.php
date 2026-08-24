<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slim4MVC API Documentation</title>
    {{-- Local, not a CDN: default-src 'self' blocked all three of these, so this
         page rendered an empty container. --}}
    <link rel="stylesheet" href="{{ asset('css/swagger-ui.css') }}">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .swagger-ui .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .swagger-ui .topbar .download-url-wrapper {
            display: none;
        }
        .swagger-ui .info .title {
            color: #333;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="{{ asset('js/swagger-ui-bundle.js') }}"></script>
    <script src="{{ asset('js/swagger-ui-standalone-preset.js') }}"></script>
    <script src="{{ asset('js/swagger-init.js') }}"></script>
</body>
</html>
