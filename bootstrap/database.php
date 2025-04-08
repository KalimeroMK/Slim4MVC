<?php

// bootstrap/database.php

declare(strict_types=1);

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

if (! isset($dotenv)) {
    $projectRoot = dirname(__DIR__, 1);
    $dotenv = Dotenv::createUnsafeImmutable($projectRoot);
    $dotenv->load();
}

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'] ?? 'forge',
    'username' => $_ENV['DB_USERNAME'] ?? 'forge',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

return $capsule;
