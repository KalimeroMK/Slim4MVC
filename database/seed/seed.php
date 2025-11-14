<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Database\Seed\DatabaseSeeder;
use Illuminate\Database\Capsule\Manager as Capsule;

// Setup Eloquent
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'slim_db',
    'database' => $_ENV['DB_DATABASE'] ?? 'slim',
    'username' => $_ENV['DB_USERNAME'] ?? 'slim',
    'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Run seeder
$seeder = new DatabaseSeeder();
$seeder->run();
