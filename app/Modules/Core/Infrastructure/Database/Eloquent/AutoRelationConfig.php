<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Database\Eloquent;

/**
 * Configuration for automatic relation loading.
 *
 * This class provides global configuration for the auto-eager-loading feature.
 * It allows fine-grained control over which models should auto-load relations
 * and provides performance optimization settings.
 */
final class AutoRelationConfig
{
    /** @var bool Whether auto-detection of relations is enabled globally */
    private static bool $autoDetectionEnabled = false;

    /** @var list<class-string> List of model classes that should auto-load relations */
    private static array $enabledModels = [];

    /** @var list<class-string> List of model classes that should NOT auto-load relations */
    private static array $disabledModels = [];

    /** @var bool Whether to enable lazy loading detection (for debugging N+1) */
    private static bool $lazyLoadingDetection = false;

    /** @var int Maximum number of relations to auto-load (safety limit) */
    private static int $maxAutoLoadRelations = 10;

    /**
     * Enable auto-detection globally for all models using the trait.
     * Use with caution - can impact performance if models have many relations.
     */
    public static function enableGlobally(): void
    {
        self::$autoDetectionEnabled = true;
    }

    /**
     * Disable auto-detection globally.
     */
    public static function disableGlobally(): void
    {
        self::$autoDetectionEnabled = false;
    }

    /**
     * Check if auto-detection is enabled globally.
     */
    public static function isAutoDetectionEnabled(): bool
    {
        return self::$autoDetectionEnabled;
    }

    /**
     * Enable auto-loading for specific model class(es).
     *
     * @param class-string|array<class-string> $modelClass
     */
    public static function enableFor(string|array $modelClass): void
    {
        $classes = is_array($modelClass) ? $modelClass : [$modelClass];

        foreach ($classes as $class) {
            if (! in_array($class, self::$enabledModels, true)) {
                self::$enabledModels[] = $class;
            }

            // Remove from disabled if present
            self::$disabledModels = array_values(array_filter(
                self::$disabledModels,
                fn ($c) => $c !== $class
            ));
        }
    }

    /**
     * Disable auto-loading for specific model class(es).
     *
     * @param class-string|array<class-string> $modelClass
     */
    public static function disableFor(string|array $modelClass): void
    {
        $classes = is_array($modelClass) ? $modelClass : [$modelClass];

        foreach ($classes as $class) {
            if (! in_array($class, self::$disabledModels, true)) {
                self::$disabledModels[] = $class;
            }

            // Remove from enabled if present
            self::$enabledModels = array_values(array_filter(
                self::$enabledModels,
                fn ($c) => $c !== $class
            ));
        }
    }

    /**
     * Check if auto-loading is enabled for a specific model.
     *
     * @param class-string $modelClass
     */
    public static function isEnabledFor(string $modelClass): bool
    {
        // If explicitly disabled, return false
        if (in_array($modelClass, self::$disabledModels, true)) {
            return false;
        }

        // If explicitly enabled, return true
        if (in_array($modelClass, self::$enabledModels, true)) {
            return true;
        }

        // Otherwise, follow global setting
        return self::$autoDetectionEnabled;
    }

    /**
     * Enable lazy loading detection (throws exception on lazy load).
     * Useful for debugging N+1 issues during development.
     */
    public static function enableLazyLoadingDetection(): void
    {
        self::$lazyLoadingDetection = true;

        // Configure Eloquent to throw exceptions on lazy loading
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(true);
    }

    /**
     * Disable lazy loading detection.
     */
    public static function disableLazyLoadingDetection(): void
    {
        self::$lazyLoadingDetection = false;

        \Illuminate\Database\Eloquent\Model::preventLazyLoading(false);
    }

    /**
     * Check if lazy loading detection is enabled.
     */
    public static function isLazyLoadingDetectionEnabled(): bool
    {
        return self::$lazyLoadingDetection;
    }

    /**
     * Set maximum number of relations to auto-load.
     */
    public static function setMaxAutoLoadRelations(int $max): void
    {
        self::$maxAutoLoadRelations = $max;
    }

    /**
     * Get maximum number of relations to auto-load.
     */
    public static function getMaxAutoLoadRelations(): int
    {
        return self::$maxAutoLoadRelations;
    }

    /**
     * Reset all configuration to defaults.
     */
    public static function reset(): void
    {
        self::$autoDetectionEnabled = false;
        self::$enabledModels = [];
        self::$disabledModels = [];
        self::$lazyLoadingDetection = false;
        self::$maxAutoLoadRelations = 10;
    }

    /**
     * Configure from array (useful for config files).
     *
     * @param array<string, mixed> $config
     */
    public static function configure(array $config): void
    {
        if (isset($config['enabled'])) {
            $config['enabled'] ? self::enableGlobally() : self::disableGlobally();
        }

        if (isset($config['enable_for'])) {
            self::enableFor($config['enable_for']);
        }

        if (isset($config['disable_for'])) {
            self::disableFor($config['disable_for']);
        }

        if (isset($config['lazy_loading_detection'])) {
            $config['lazy_loading_detection']
                ? self::enableLazyLoadingDetection()
                : self::disableLazyLoadingDetection();
        }

        if (isset($config['max_relations'])) {
            self::setMaxAutoLoadRelations($config['max_relations']);
        }
    }
}
