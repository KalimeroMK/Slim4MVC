<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

// Load environment variables for testing
if (file_exists(__DIR__.'/../.env.testing')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..', '.env.testing');
    $dotenv->load();
}

// Set up test database
use Database\Migrations\CreateFailedJobsTable;
use Database\Migrations\CreatePermissionRoleTable;
use Database\Migrations\CreatePermissionTable;
use Database\Migrations\CreateRoleTable;
use Database\Migrations\CreateRoleUserTable;
use Database\Migrations\CreateUsersTable;
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'] ?? 'sqlite',
    'database' => $_ENV['DB_DATABASE'] ?? ':memory:',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Run migrations for testing (only if in memory database)
if (($_ENV['DB_DATABASE'] ?? ':memory:') === ':memory:') {
    runTestMigrations();
}

/**
 * Run migrations for testing environment
 */
function runTestMigrations(): void
{
    $migrations = [
        CreateUsersTable::class,
        CreateRoleTable::class,
        CreateRoleUserTable::class,
        CreatePermissionTable::class,
        CreatePermissionRoleTable::class,
        CreateFailedJobsTable::class,
    ];

    foreach ($migrations as $migration) {
        try {
            (new $migration)->up();
        } catch (\Exception) {
            // Table might already exist, continue
        }
    }
}
