<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Database\Migrations\CreateFailedJobsTable;
use Database\Migrations\CreatePermissionRoleTable;
use Database\Migrations\CreatePermissionTable;
use Database\Migrations\CreateRoleTable;
use Database\Migrations\CreateRoleUserTable;
use Database\Migrations\CreateUsersTable;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load .env file
$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->safeLoad();

// 1. Setup Eloquent
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'] ?? 'slim',
    'username' => $_ENV['DB_USERNAME'] ?? 'slim',
    'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// 2. Helpers
function now()
{
    return date('Y-m-d H:i:s');
}

function migrationTableExists(): bool
{
    return Capsule::schema()->hasTable('migrations');
}

function createMigrationsTable(): void
{
    Capsule::schema()->create('migrations', function ($table) {
        $table->increments('id');
        $table->string('migration')->unique();
        $table->integer('batch');
        $table->timestamps();
    });
}

function migrationAlreadyRun(string $migrationName): bool
{
    return Capsule::table('migrations')->where('migration', $migrationName)->exists();
}

function storeMigration(string $migrationName, int $batch = 1): void
{
    Capsule::table('migrations')->insert([
        'migration' => $migrationName,
        'batch' => $batch,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function deleteMigrationRecord(string $migrationName): void
{
    Capsule::table('migrations')->where('migration', $migrationName)->delete();
}

// 3. All migration classes (ordered!)
$migrations = [
    CreateUsersTable::class,
    CreateRoleTable::class,
    CreateRoleUserTable::class,
    CreatePermissionTable::class,
    CreatePermissionRoleTable::class,
    CreateFailedJobsTable::class,
];

// 4. Run Command
$command = $argv[1] ?? 'migrate';

if (! migrationTableExists()) {
    createMigrationsTable();
    echo "✅ Migrations table created.\n";
}

switch ($command) {
    case 'migrate':
        $currentBatch = (int) Capsule::table('migrations')->max('batch') + 1;

        foreach ($migrations as $migrationClass) {
            if (migrationAlreadyRun($migrationClass)) {
                echo "⏭️  Skipping already run: {$migrationClass}\n";

                continue;
            }

            try {
                (new $migrationClass)->up();
                storeMigration($migrationClass, $currentBatch);
                echo "✅ Migrated: {$migrationClass}\n";
            } catch (Exception $e) {
                echo "❌ Error: {$e->getMessage()}\n";
            }
        }
        break;

    case 'rollback':
        $lastBatch = (int) Capsule::table('migrations')->max('batch');

        if ($lastBatch === 0) {
            echo "ℹ️  No migrations to rollback.\n";
            break;
        }

        $batchMigrations = Capsule::table('migrations')->where('batch', $lastBatch)->get();

        foreach ($batchMigrations as $migration) {
            $className = $migration->migration;
            if (class_exists($className)) {
                try {
                    (new $className)->down();
                    deleteMigrationRecord($className);
                    echo "🔁 Rolled back: {$className}\n";
                } catch (Exception $e) {
                    echo "❌ Error during rollback: {$e->getMessage()}\n";
                }
            } else {
                echo "⚠️  Class not found: {$className}\n";
            }
        }
        break;

    case 'refresh':
        echo "♻️  Refreshing all migrations...\n";
        foreach (array_reverse($migrations) as $migrationClass) {
            if (migrationAlreadyRun($migrationClass)) {
                try {
                    (new $migrationClass)->down();
                    deleteMigrationRecord($migrationClass);
                    echo "🔁 Rolled back: {$migrationClass}\n";
                } catch (Exception $e) {
                    echo "❌ Error during refresh rollback: {$e->getMessage()}\n";
                }
            }
        }

        // Run all migrations again
        $batch = 1;
        foreach ($migrations as $migrationClass) {
            try {
                (new $migrationClass)->up();
                storeMigration($migrationClass, $batch);
                echo "✅ Migrated: {$migrationClass}\n";
            } catch (Exception $e) {
                echo "❌ Error during refresh migrate: {$e->getMessage()}\n";
            }
        }
        break;

    default:
        echo "❓ Unknown command: {$command}\n";
        echo "Usage:\n";
        echo "  php migrate.php migrate   # Run pending migrations\n";
        echo "  php migrate.php rollback  # Rollback last batch\n";
        echo "  php migrate.php refresh   # Rollback all and re-run\n";
        break;
}
