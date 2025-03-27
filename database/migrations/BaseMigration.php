<?php

namespace Database\Migrations;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;

class BaseMigration extends Migration
{
    public function __construct()
    {
        $projectRoot = dirname(__DIR__, 2);
        $envPath = $projectRoot . '/.env';

        if (!file_exists($envPath)) {
            echo ".env file not found at: $envPath\n";
        } else {
            echo ".env file found at: $envPath\n";
        }

        $dotenv = Dotenv::createUnsafeImmutable($projectRoot);
        $dotenv->load();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => $_ENV['DB_CONNECTION'] ?? '',
            'host'      => $_ENV['DB_HOST'] ?? '',
            'port'      => $_ENV['DB_PORT'] ?? '',
            'database'  => $_ENV['DB_DATABASE'] ?? '',
            'username'  => $_ENV['DB_USERNAME'] ?? '',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Optionally, test the connection with a simple query:
        $result = Capsule::select('SELECT VERSION() as version');
        var_dump($result);
    }
}
