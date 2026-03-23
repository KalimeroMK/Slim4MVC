<?php

declare(strict_types=1);

/* Global helper functions */

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

if (! function_exists('old')) {
    /**
     * @param  string|int  $key
     * @return mixed
     */
    function old($key)
    {
        /** @var array<array-key, mixed>|null $input */
        $input = $_SESSION['old_input'] ?? null;

        if (! is_array($input)) {
            return null;
        }

        return $input[$key] ?? null;
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return __DIR__.('/../'.$path);
    }
}

if (! function_exists('env')) {
    /**
     * @param  mixed  $default
     */
    function env(string $key, $default = false): mixed
    {
        $value = getenv($key);

        throw_when(! $value && ! $default, $key.' is not a defined .env variable and has not default value');

        return $value ?: $default;
    }
}

if (! function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config/'.$path);
    }
}

if (! function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('app/'.$path);
    }
}

if (! function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return base_path('database/'.$path);
    }
}

if (! function_exists('throw_when')) {
    /**
     * @param  class-string<Throwable>  $exception
     */
    function throw_when(bool $fails, string $message, string $exception = Exception::class): void
    {
        if (! $fails) {
            return;
        }

        throw new $exception($message);
    }
}

if (! function_exists('config')) {
    /**
     * @param  int|array<string>|string|null  $path
     */
    function config(int|array|string|null $path = null): mixed
    {
        $config = [];
        /** @var list<string>|false $folder */
        $folder = scandir(config_path());
        if ($folder === false) {
            return null;
        }
        $config_files = array_slice($folder, 2);

        foreach ($config_files as $config_file) {
            throw_when(
                Str::after($config_file, '.') !== 'php',
                'Config files must be .php files'
            );
            data_set($config, Str::before($config_file, '.php'), require config_path($config_file));
        }

        return data_get($config, $path);
    }
}

if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  int|array<string>|string|null  $key
     */
    function data_get(mixed $target, int|array|string|null $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', (string) $key);

        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (! function_exists('data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param  array<string>|string  $key
     */
    function data_set(mixed &$target, array|string $key, mixed $value, bool $overwrite = true): mixed
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        $segment = array_shift($segments);
        if ($segment === '*') {
            if (! Arr::accessible($target)) {
                $target = [];
            }

            if ($segments !== []) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target) && $segment !== null) {
            if ($segments !== []) {
                if (! Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target) && $segment !== null) {
            if ($segments !== []) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } elseif ($segment !== null) {
            $target = [];

            if ($segments !== []) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }

}
