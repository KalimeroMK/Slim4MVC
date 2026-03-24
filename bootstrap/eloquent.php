<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Providers\AutoEagerLoadServiceProvider;

/**
 * Eloquent ORM Configuration Bootstrap
 *
 * This file configures Eloquent-specific features like auto eager loading.
 */

// Load configuration
$config = require __DIR__.'/../config/eloquent.php';

// Configure auto eager loading
$autoEagerConfig = $config['auto_eager_loading'] ?? [];

// Convert mode to actual configuration
if (($autoEagerConfig['enabled'] ?? false) === true) {
    $mode = $autoEagerConfig['mode'] ?? 'explicit';

    if ($mode === 'detection') {
        $autoEagerConfig['enabled'] = true;
    } else {
        // explicit mode - only enable for specific models
        $autoEagerConfig['enabled'] = false;
    }
}

// Add lazy loading detection config
$autoEagerConfig['lazy_loading_detection'] = $config['lazy_loading_detection']['enabled'] ?? false;

// Register the service provider
AutoEagerLoadServiceProvider::register($autoEagerConfig);
