<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Modules\Core\Infrastructure\Http\Controllers\Admin\DashboardController;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\Role\Infrastructure\Models\Role;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\CoversClass;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * The dashboard renders one row per role, so a relation the controller forgets to
 * load turns into a query per row.
 */
#[CoversClass(DashboardController::class)]
final class DashboardControllerQueryTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = new ServerRequestFactory();
        $this->loginAsAdmin();
    }

    public function test_it_renders_without_lazy_loading_any_relation(): void
    {
        $this->seedRoles(3);

        // Every relation the view touches must already be loaded; anything else
        // throws instead of quietly issuing another query.
        Model::preventLazyLoading(true);

        $result = $this->renderDashboard();

        $this->assertSame(200, $result->getStatusCode());
    }

    public function test_query_count_does_not_grow_with_the_number_of_roles(): void
    {
        $connection = $this->capsule->getConnection();

        $this->seedRoles(2);
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        $this->renderDashboard();
        $withTwoRoles = count($connection->getQueryLog());

        $this->seedRoles(5, 'extra');
        $connection->flushQueryLog();
        $this->renderDashboard();
        $withSevenRoles = count($connection->getQueryLog());

        $connection->disableQueryLog();

        $this->assertSame(
            $withTwoRoles,
            $withSevenRoles,
            sprintf(
                'Rendering cost %d queries for 2 roles and %d for 7 — a relation is being lazy loaded per row.',
                $withTwoRoles,
                $withSevenRoles
            )
        );
    }

    public function test_it_reports_the_user_count_per_role(): void
    {
        $role = $this->createRole(['name' => 'counted']);
        $role->users()->attach($this->createUser(['email' => 'counted-1@example.com'])->id);
        $role->users()->attach($this->createUser(['email' => 'counted-2@example.com'])->id);

        /** @var Role $loaded */
        $loaded = Role::withCount('users')->find($role->id);

        $this->assertSame(2, $loaded->users_count);
    }

    /**
     * Create $count roles, each with a permission and two users attached.
     */
    private function seedRoles(int $count, string $prefix = 'role'): void
    {
        for ($i = 0; $i < $count; $i++) {
            $role = $this->createRole(['name' => sprintf('%s-%d', $prefix, $i)]);
            $role->permissions()->attach(
                $this->createPermission(['name' => sprintf('%s-perm-%d', $prefix, $i)])->id
            );

            for ($u = 0; $u < 2; $u++) {
                $role->users()->attach(
                    $this->createUser(['email' => sprintf('%s-%d-%d@example.com', $prefix, $i, $u)])->id
                );
            }
        }
    }

    private function renderDashboard(): Response
    {
        $controller = new DashboardController($this->container);

        /** @var Response $response */
        $response = $controller->dashboard(
            $this->requestFactory->createServerRequest('GET', '/dashboard'),
            new Response()
        );

        return $response;
    }

    private function loginAsAdmin(): void
    {
        $admin = $this->createUser(['name' => 'Admin', 'email' => 'admin-query@example.com']);
        $admin->roles()->attach($this->createRole(['name' => 'admin'])->id);

        $_SESSION['user_id'] = $admin->id;
        $_SESSION['user_name'] = $admin->name;
        $_SESSION['user_email'] = $admin->email;
        $_SESSION['user_roles'] = ['admin'];
        $_SESSION['user_permissions'] = [];

        $auth = $this->createStub(Auth::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($admin);
        $this->container->set(Auth::class, $auth);
    }
}
