<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

// Load environment variables for testing
if (file_exists(__DIR__.'/../.env.testing')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..', '.env.testing');
    $dotenv->load();
}

// Set up test database
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'] ?? 'sqlite',
    'database' => $_ENV['DB_DATABASE'] ?? ':memory:',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();
