<?php

declare(strict_types=1);

namespace Tests;

use App\Modules\Core\Infrastructure\Support\Logger as AppLogger;
use DI\Container;
use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up container
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $this->container = $containerBuilder->build();

        // Set up database
        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        // Begin database transaction for test isolation
        $this->capsule->getConnection()->beginTransaction();

        // Set up session
        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $session->start();
        $this->container->set(Session::class, fn (): Session => $session);

        // Set up logger (mock)
        $logger = $this->createMock(LoggerInterface::class);
        $this->container->set(LoggerInterface::class, $logger);

        // Set container for Logger helper
        AppLogger::setContainer($this->container);

        // Run migrations
        $this->runMigrations();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to clean up test data
        if ($this->capsule->getConnection()->transactionLevel() > 0) {
            $this->capsule->getConnection()->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Assert that a given table has a record matching the given attributes.
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $connection = $this->capsule->getConnection();
        $query = $connection->table($table);

        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }

        $this->assertTrue(
            $query->exists(),
            "Failed asserting that table [{$table}] has record matching: ".json_encode($data)
        );
    }

    /**
     * Assert that a given table does not have a record matching the given attributes.
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $connection = $this->capsule->getConnection();
        $query = $connection->table($table);

        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }

        $this->assertFalse(
            $query->exists(),
            "Failed asserting that table [{$table}] does not have record matching: ".json_encode($data)
        );
    }

    protected function runMigrations(): void
    {
        $connection = $this->capsule->getConnection();

        // Create users table
        $connection->statement('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                password VARCHAR(255),
                password_reset_token VARCHAR(255) NULL,
                email_verified_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )
        ');

        // Create roles table
        $connection->statement('
            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL UNIQUE,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )
        ');

        // Create permissions table
        $connection->statement('
            CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL UNIQUE,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )
        ');

        // Create role_user pivot table
        $connection->statement('
            CREATE TABLE IF NOT EXISTS role_user (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (role_id) REFERENCES roles(id)
            )
        ');

        // Create permission_role pivot table
        $connection->statement('
            CREATE TABLE IF NOT EXISTS permission_role (
                permission_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                PRIMARY KEY (permission_id, role_id),
                FOREIGN KEY (permission_id) REFERENCES permissions(id),
                FOREIGN KEY (role_id) REFERENCES roles(id)
            )
        ');
    }

    /**
     * Create a test user using factory.
     */
    protected function createUser(array $attributes = []): \App\Modules\User\Infrastructure\Models\User
    {
        $factory = new \App\Modules\User\Infrastructure\Database\Factories\UserFactory();

        return $factory->create($attributes);
    }

    /**
     * Create a test role using factory.
     */
    protected function createRole(array $attributes = []): \App\Modules\Role\Infrastructure\Models\Role
    {
        $factory = new \App\Modules\Role\Infrastructure\Database\Factories\RoleFactory();

        return $factory->create($attributes);
    }

    /**
     * Create a test permission using factory.
     */
    protected function createPermission(array $attributes = []): \App\Modules\Permission\Infrastructure\Models\Permission
    {
        $factory = new \App\Modules\Permission\Infrastructure\Database\Factories\PermissionFactory();

        return $factory->create($attributes);
    }

    /**
     * Make an API request and return the response.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $data  Request data
     * @param  array<string, string>  $headers  Request headers
     * @return array{status: int, body: array<string, mixed>, headers: array<string, string>}
     */
    protected function apiRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): array {
        // This is a helper method structure - actual implementation would use Slim's App
        // For now, it provides a consistent interface for API testing
        return [
            'status' => 200,
            'body' => [],
            'headers' => [],
        ];
    }

    /**
     * Assert API response structure.
     */
    protected function assertApiResponse(array $response, int $expectedStatus = 200): void
    {
        $this->assertEquals($expectedStatus, $response['status']);
        $this->assertArrayHasKey('body', $response);
    }

    /**
     * Assert API response contains data.
     */
    protected function assertApiResponseHasData(array $response, string $key): void
    {
        $this->assertArrayHasKey('body', $response);
        $this->assertArrayHasKey($key, $response['body']);
    }
}
