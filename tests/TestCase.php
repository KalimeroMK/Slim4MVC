<?php

declare(strict_types=1);

namespace Tests;

use App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig;
use App\Modules\Core\Infrastructure\Database\Eloquent\RelationCache;
use App\Modules\Core\Infrastructure\Support\AuthHelper;
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
    /**
     * Secret used for JwtService in tests. Fixed rather than read from $_ENV: the
     * container factory below is lazy, so it would otherwise pick up whatever a
     * previously-run test left in the environment.
     */
    protected const string TEST_JWT_SECRET = 'test-secret-key-that-is-at-least-32-chars';

    protected Container $container;

    protected Capsule $capsule;

    /** @var array<string, mixed> */
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();

        // $_ENV is process-wide, and several tests overwrite it wholesale. Snapshot it
        // so a test cannot change how later tests read configuration.
        $this->originalEnv = $_ENV;

        $this->resetAuthState();
        $this->resetEloquentState();

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
        $mockArraySessionStorage = new MockArraySessionStorage();
        $session = new Session($mockArraySessionStorage);
        $session->start();

        $this->container->set(Session::class, fn (): Session => $session);
        $this->container->set(LoggerInterface::class, $this->createStub(LoggerInterface::class));
        $this->container->set(
            \App\Modules\Core\Infrastructure\Support\JwtService::class,
            fn (): \App\Modules\Core\Infrastructure\Support\JwtService => new \App\Modules\Core\Infrastructure\Support\JwtService(
                self::TEST_JWT_SECRET
            )
        );

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

        // Also clear on the way out, so tests that do not extend this class are not
        // handed a logged-in user either.
        $this->resetAuthState();
        $this->resetEloquentState();
        $_ENV = $this->originalEnv;

        parent::tearDown();
    }

    /**
     * Clear authentication state that outlives a single test.
     *
     * AuthHelper keeps the current user in a static property and mirrors it into
     * $_SESSION, neither of which PHPUnit resets. Any test that logged in would
     * otherwise leak that user into every test that ran after it, making results
     * depend on execution order.
     */
    protected function resetAuthState(): void
    {
        $_SESSION = [];
        AuthHelper::logout();
    }

    /**
     * Clear Eloquent state that outlives a single test.
     *
     * Auto-eager-loading config, the detected-relation cache and Eloquent's global
     * lazy-loading guard are all static. A test that enables lazy-loading detection
     * would otherwise make every later test throw on the first lazy load.
     */
    protected function resetEloquentState(): void
    {
        AutoRelationConfig::reset();
        RelationCache::clear();
    }

    /**
     * Assert that a given table has a record matching the given attributes.
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $connection = $this->capsule->getConnection();
        $builder = $connection->table($table);

        foreach ($data as $key => $value) {
            $builder->where($key, $value);
        }

        $this->assertTrue(
            $builder->exists(),
            sprintf('Failed asserting that table [%s] has record matching: ', $table).json_encode($data)
        );
    }

    /**
     * Assert that a given table does not have a record matching the given attributes.
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $connection = $this->capsule->getConnection();
        $builder = $connection->table($table);

        foreach ($data as $key => $value) {
            $builder->where($key, $value);
        }

        $this->assertFalse(
            $builder->exists(),
            sprintf('Failed asserting that table [%s] does not have record matching: ', $table).json_encode($data)
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
                password_reset_token_expires_at DATETIME NULL,
                email_verified_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        // Create roles table
        $connection->statement('
            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL UNIQUE,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
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
        $userFactory = new \App\Modules\User\Infrastructure\Database\Factories\UserFactory();

        return $userFactory->create($attributes);
    }

    /**
     * Create a test role using factory.
     */
    protected function createRole(array $attributes = []): \App\Modules\Role\Infrastructure\Models\Role
    {
        $roleFactory = new \App\Modules\Role\Infrastructure\Database\Factories\RoleFactory();

        return $roleFactory->create($attributes);
    }

    /**
     * Create a test permission using factory.
     */
    protected function createPermission(array $attributes = []): \App\Modules\Permission\Infrastructure\Models\Permission
    {
        $permissionFactory = new \App\Modules\Permission\Infrastructure\Database\Factories\PermissionFactory();

        return $permissionFactory->create($attributes);
    }
}
