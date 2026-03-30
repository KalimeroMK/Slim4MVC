<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Providers;

use App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig;

/**
 * Service Provider for Auto Eager Loading configuration.
 *
 * This provider configures the automatic eager loading feature
 * based on application configuration.
 */
class AutoEagerLoadServiceProvider
{
    /**
     * Register and configure auto eager loading.
     *
     * @param array<string, mixed> $config Configuration array
     */
    public static function register(array $config = []): void
    {
        // Apply configuration
        AutoRelationConfig::configure($config);

        // In development environment, enable lazy loading detection
        $environment = $_ENV['APP_ENV'] ?? 'production';

        // Only enable if explicitly configured or if detection is enabled
        if (($environment === 'development' || $environment === 'local') && ($config['lazy_loading_detection'] ?? false)) {
            AutoRelationConfig::enableLazyLoadingDetection();
        }
    }

    /**
     * Boot the provider - can be called after container is ready.
     */
    public static function boot(): void
    {
        // Any runtime initialization can go here
    }
}
