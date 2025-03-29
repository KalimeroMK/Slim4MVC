<?php

declare(strict_types=1);

if (! function_exists('asset')) {
    function asset($path): string
    {
        return '/public/'.ltrim($path, '/');
    }
}
