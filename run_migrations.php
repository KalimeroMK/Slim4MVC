<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';  // Ensure Composer autoload is included

use Database\Migrations\CreateUsersTable;
use Illuminate\Database\Capsule\Manager as Capsule;

// Function to check if the migration table exists
function migrationTableExists(): bool
{
    return Capsule::schema()->hasTable('migrations');
}

// Function to create the migrations table if it doesn't exist
function createMigrationsTable(): void
{
    Capsule::schema()->create('migrations', function ($table) {
        $table->increments('id');
        $table->string('migration');
        $table->integer('batch');
        $table->timestamps();
    });
}

// Function to check if the migration has already been run
function migrationAlreadyRun($migrationName): bool
{
    return Capsule::table('migrations')->where('migration', $migrationName)->exists();
}

// Function to store migration name in the migrations table
function storeMigration($migrationName): void
{
    Capsule::table('migrations')->insert([
        'migration' => $migrationName,
        'batch' => 1,  // Default batch number, you can increment if necessary
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Method to get current time (optional)
function now()
{
    return date('Y-m-d H:i:s');
}

// Initialize Capsule (Eloquent ORM) for database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'slim_db',
    'database' => 'slim',
    'username' => 'slim',
    'password' => 'secret',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

// Set Capsule as the global database manager
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Check and create migrations table if it doesn't exist
if (! migrationTableExists()) {
    createMigrationsTable();
    echo "Migrations table created.\n";
}

// Array of migrations to run
$migrations = [
    CreateUsersTable::class,
    // Add other migrations here
];

// Run Migrations
foreach ($migrations as $migrationClass) {
    $migrationName = $migrationClass;  // Use the class name as the migration name

    // Check if the migration has already been run
    if (! migrationAlreadyRun($migrationName)) {
        try {
            // Run the migration
            $migration = new $migrationClass;
            $migration->up();

            // After running the migration, store it in the migrations table
            storeMigration($migrationName);

            echo "Migration {$migrationName} applied.\n";
        } catch (Exception $e) {
            echo "Error while applying migration {$migrationName}: ".$e->getMessage()."\n";
        }
    } else {
        echo "Migration {$migrationName} has already been run.\n";
    }
}
