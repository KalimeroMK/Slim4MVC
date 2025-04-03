<?php

declare(strict_types=1);

/* Global helper functions */

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

if (! function_exists('old')) {
    function old($key)
    {
        $input = app()->resolve('old_input');

        $field = collect($input)->filter(fn ($value, $field): bool => $key === $field);

        if (isset($field[$key])) {
            return $field[$key];
        }

        return null;
    }
}
if (! function_exists('base_path')) {
    function base_path($path = ''): string
    {
        return __DIR__."/../{$path}";
    }
}

if (! function_exists('env')) {
    function env($key, $default = false): bool
    {
        $value = getenv($key);

        throw_when(! $value && ! $default, "{$key} is not a defined .env variable and has not default value");

        return $value || $default;
    }
}

if (! function_exists('config_path')) {
    function config_path($path = ''): string
    {
        return base_path("config/{$path}");
    }
}

if (! function_exists('app_path')) {
    function app_path($path = ''): string
    {
        return base_path("app/{$path}");
    }
}

if (! function_exists('database_path')) {
    function database_path($path = ''): string
    {
        return base_path("database/{$path}");
    }
}

if (! function_exists('throw_when')) {
    function throw_when(bool $fails, string $message, string $exception = Exception::class): void
    {
        if (! $fails) {
            return;
        }

        throw new $exception($message);
    }
}

if (! function_exists('config')) {
    function config($path = null): mixed
    {
        $config = [];
        $folder = scandir(config_path());
        $config_files = array_slice($folder, 2, count($folder));

        foreach ($config_files as $file) {
            throw_when(
                Str::after($file, '.') !== 'php',
                'Config files must be .php files'
            );
            data_set($config, Str::before($file, '.php'), require config_path($file));
        }

        return data_get($config, $path);
    }
}

if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     */
    function data_get(mixed $target, int|array|string|null $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

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
     */
    function data_set(mixed &$target, array|string $key, mixed $value, bool $overwrite = true): mixed
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
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
        } elseif (Arr::accessible($target)) {
            if ($segments !== []) {
                if (! Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments !== []) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
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
