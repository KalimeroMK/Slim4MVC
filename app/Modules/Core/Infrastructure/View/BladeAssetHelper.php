<?php

declare(strict_types=1);

if (! function_exists('asset')) {
    function asset(string $path): string
    {
        return '/'.mb_ltrim($path, '/');
    }
}

if (! function_exists('url')) {
    function url(string $path = ''): string
    {
        return '/'.mb_ltrim($path, '/');
    }
}

if (! function_exists('route')) {
    /**
     * Generate URL for a named route.
     *
     * @param  array<string, string|int>  $params
     */
    function route(string $name, array $params = []): string
    {
        return App\Modules\Core\Infrastructure\Support\Route::url($name, $params);
    }
}
