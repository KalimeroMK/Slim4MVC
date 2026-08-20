<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

/**
 * Filesystem locations, resolved in one place.
 *
 * Runtime paths used to be built by counting `../` segments or dirname() levels at
 * each call site, and the count was wrong four separate times: the file queue wrote
 * into app/Modules/Core/storage, the autowiring cache into app/storage, the file
 * cache one level *above* the project root, and the queued mailer at a views
 * directory that does not exist. Anything writable or asset-like should come from
 * here so the depth is stated once.
 */
final class Paths
{
    /**
     * Project root.
     *
     * Five levels up from app/Modules/Core/Infrastructure/Support.
     */
    public static function root(): string
    {
        return dirname(__DIR__, 5);
    }

    /**
     * A path inside the writable storage directory.
     */
    public static function storage(string $path = ''): string
    {
        return self::join(self::root().'/storage', $path);
    }

    /**
     * A path inside the resources directory (views, translations).
     */
    public static function resources(string $path = ''): string
    {
        return self::join(self::root().'/resources', $path);
    }

    private static function join(string $base, string $path): string
    {
        $path = trim($path, '/');

        return $path === '' ? $base : $base.'/'.$path;
    }
}
