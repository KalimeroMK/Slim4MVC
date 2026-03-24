<?php

declare(strict_types=1);

/**
 * Eloquent ORM Configuration
 *
 * This file configures the automatic eager loading and relation
 * loading features for Eloquent models.
 *
 * IMPORTANT: Auto eager loading can impact performance if not used carefully.
 * It's recommended to use explicit `autoWith` property on models or the
 * `preload()` helper function instead of global auto-detection.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Eager Loading
    |--------------------------------------------------------------------------
    |
    | When enabled, relations will be automatically eager loaded.
    | There are three modes:
    |
    | 1. 'disabled' (default) - No auto loading
    | 2. 'explicit' - Only models with `autoWith` property are auto-loaded
    | 3. 'detection' - Auto-detect all relations (can impact performance)
    |
    */
    'auto_eager_loading' => [
        // Enable/disable globally
        'enabled' => $_ENV['ELOQUENT_AUTO_EAGER_LOADING'] ?? false,

        // Mode: 'explicit' or 'detection'
        'mode' => $_ENV['ELOQUENT_AUTO_MODE'] ?? 'explicit',

        // Maximum number of relations to auto-load (safety limit)
        'max_relations' => 10,

        // Specific models to enable/disable (class names)
        'enable_for' => [
            // App\Modules\User\Infrastructure\Models\User::class,
        ],

        'disable_for' => [
            // App\Modules\User\Infrastructure\Models\User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Detection
    |--------------------------------------------------------------------------
    |
    | When enabled, accessing a relation that hasn't been eager loaded
    | will throw an exception. This is useful for debugging N+1 problems
    | during development.
    |
    | WARNING: Never enable this in production!
    |
    */
    'lazy_loading_detection' => [
        // Enable in development only
        'enabled' => ($_ENV['APP_ENV'] ?? 'production') === 'development'
            && ($_ENV['ELOQUENT_LAZY_LOADING_DETECTION'] ?? false),
    ],
];
