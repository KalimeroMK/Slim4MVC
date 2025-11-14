<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Traits;

trait RouteParamsTrait
{
    /**
     * Retrieve a route parameter as an integer.
     *
     * This method fetches the parameter with the specified key from the given
     * associative array and returns its value cast to an integer. It trims the
     * value first to remove any extra spaces.
     *
     * @param  array  $args  The associative array of route parameters.
     * @param  string  $key  The key for the desired parameter (e.g., 'id').
     * @param  int  $default  The default value to return if the key is not found.
     */
    protected function getParamAsInt(array $args, string $key, int $default = 0): int
    {
        if (isset($args[$key])) {
            return (int) mb_trim((string) $args[$key]);
        }

        return $default;
    }

    /**
     * Retrieve a route parameter as a string.
     *
     * This method fetches the parameter with the specified key from the given
     * associative array and returns its value as a trimmed string.
     *
     * @param  array  $args  The associative array of route parameters.
     * @param  string  $key  The key for the desired parameter.
     * @param  string  $default  The default value to return if the key is not found.
     */
    protected function getParamAsString(array $args, string $key, string $default = ''): string
    {
        return isset($args[$key]) ? mb_trim((string) $args[$key]) : $default;
    }
}
