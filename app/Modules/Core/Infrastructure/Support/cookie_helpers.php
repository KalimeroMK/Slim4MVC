<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Support\CookieHelper;

if (! function_exists('cookie')) {
    /**
     * Get/set cookie values.
     *
     * Usage:
     *   cookie()                  // Get CookieHelper instance
     *   cookie('name')            // Get cookie value
     *   cookie('name', 'value')   // Set cookie value
     *   cookie('name', 'value', 3600)  // Set with TTL
     *
     *
     * @return CookieHelper|mixed
     */
    function cookie(?string $name = null, mixed $value = null, ?int $ttl = null): mixed
    {
        $cookieHelper = CookieHelper::getInstance();

        if ($name === null) {
            return $cookieHelper;
        }

        if ($value === null && $ttl === null) {
            return $cookieHelper->get($name);
        }

        $cookieHelper->set($name, $value, $ttl);

        return null;
    }
}

if (! function_exists('cookie_get')) {
    /**
     * Get a cookie value.
     *
     * @template T
     *
     * @param  T|null  $default
     * @return T|null
     */
    function cookie_get(string $name, mixed $default = null): mixed
    {
        return CookieHelper::getInstance()->get($name, $default);
    }
}

if (! function_exists('cookie_set')) {
    /**
     * Set a cookie.
     *
     * @param  array<string, mixed>  $options
     */
    function cookie_set(string $name, mixed $value, ?int $ttl = null, array $options = []): void
    {
        CookieHelper::getInstance()->set($name, $value, $ttl, $options);
    }
}

if (! function_exists('cookie_forever')) {
    /**
     * Set a forever cookie (10 years).
     *
     * @param  array<string, mixed>  $options
     */
    function cookie_forever(string $name, mixed $value, array $options = []): void
    {
        CookieHelper::getInstance()->forever($name, $value, $options);
    }
}

if (! function_exists('cookie_has')) {
    /**
     * Check if a cookie exists.
     */
    function cookie_has(string $name): bool
    {
        return CookieHelper::getInstance()->has($name);
    }
}

if (! function_exists('cookie_delete')) {
    /**
     * Delete a cookie.
     */
    function cookie_delete(string $name): void
    {
        CookieHelper::getInstance()->delete($name);
    }
}

if (! function_exists('cookie_flush')) {
    /**
     * Delete all cookies.
     */
    function cookie_flush(): void
    {
        $cookieHelper = CookieHelper::getInstance();

        foreach (array_keys($_COOKIE) as $name) {
            $cookieHelper->delete($name);
        }
    }
}

if (! function_exists('cookie_remember')) {
    /**
     * Get cookie value or set it if not exists.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    function cookie_remember(string $name, ?int $ttl, callable $callback): mixed
    {
        $cookieHelper = CookieHelper::getInstance();

        if ($cookieHelper->has($name)) {
            $value = $cookieHelper->get($name);
            /** @phpstan-ignore-next-line */
            if ($value !== null) {
                return $value;
            }
        }

        $value = $callback();
        $cookieHelper->set($name, $value, $ttl);

        return $value;
    }
}
