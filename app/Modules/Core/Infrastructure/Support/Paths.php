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
     * Overrides the resolved root. Only tests set this.
     */
    private static ?string $rootOverride = null;

    /**
     * Project root.
     *
     * Five levels up from app/Modules/Core/Infrastructure/Support.
     */
    public static function root(): string
    {
        return self::$rootOverride ?? dirname(__DIR__, 5);
    }

    /**
     * Point every path at a different root, or pass null to restore the real one.
     *
     * This exists for tests of code that writes into the project - make:module being
     * the one that does. Without a seam such a test has to generate into the live tree
     * and put it back afterwards, and a run that is killed rather than interrupted
     * never gets to put it back: SIGKILL cannot be caught, so shutdown handlers do not
     * help. It leaves bootstrap/dependencies.php and bootstrap/modules-register.php
     * carrying registrations for a module that is no longer there, and the app does not
     * complain about those - bootstrap/modules.php logs a failing provider and carries
     * on. Generating somewhere else is the only thing that actually closes that.
     *
     * TestCase::tearDown() resets it, so no test can leak a root into another.
     */
    public static function useRoot(?string $root): void
    {
        self::$rootOverride = $root === null ? null : rtrim($root, '/');
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
