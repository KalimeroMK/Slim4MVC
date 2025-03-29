<?php

if (!function_exists('asset')) {
    function asset($path): string
    {
        return '/public/' . ltrim($path, '/');
    }
}