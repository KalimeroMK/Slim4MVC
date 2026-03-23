<?php

declare(strict_types=1);
/**
 * Clear all application caches
 * Run this after deployment or when namespaces change
 */
echo "Clearing application caches...\n\n";

// Clear view cache
$viewCache = __DIR__.'/storage/cache/view';
if (is_dir($viewCache)) {
    $files = glob($viewCache.'/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo 'Deleted: '.basename($file)."\n";
        }
    }
    echo "✓ View cache cleared\n";
}

// Clear logs (optional)
$logs = __DIR__.'/storage/logs';
if (is_dir($logs)) {
    $files = glob($logs.'/*.log');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo 'Deleted log: '.basename($file)."\n";
        }
    }
    echo "✓ Logs cleared\n";
}

// Clear composer autoload (run dump-autoload after)
echo "\nRun 'composer dump-autoload' to refresh autoload cache\n";
echo "Run 'docker compose restart' to clear PHP OPcache\n";
