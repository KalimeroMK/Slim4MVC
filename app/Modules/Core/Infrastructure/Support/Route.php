<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use InvalidArgumentException;

/**
 * Route URL generator with named route support.
 */
class Route
{
    /**
     * Named routes registry.
     *
     * @var array<string, string>
     */
    private static array $routes = [];

    /**
     * Register a named route.
     */
    public static function add(string $name, string $path): void
    {
        self::$routes[$name] = $path;
    }

    /**
     * Generate URL for a named route.
     *
     * @param  array<string, string|int>  $params
     */
    public static function url(string $name, array $params = []): string
    {
        if (! isset(self::$routes[$name])) {
            throw new InvalidArgumentException(sprintf('Route [%s] not found.', $name));
        }

        $url = self::$routes[$name];

        // Replace route parameters
        foreach ($params as $key => $value) {
            $url = str_replace(sprintf('{%s}', $key), (string) $value, $url);
        }

        return $url;
    }

    /**
     * Check if route exists.
     */
    public static function has(string $name): bool
    {
        return isset(self::$routes[$name]);
    }

    /**
     * Get all registered routes.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::$routes;
    }

    /**
     * Clear all routes (useful for testing).
     */
    public static function clear(): void
    {
        self::$routes = [];
    }
}
