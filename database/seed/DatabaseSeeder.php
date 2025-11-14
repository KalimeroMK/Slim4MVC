<?php

declare(strict_types=1);

namespace Database\Seed;

use App\Modules\Permission\Infrastructure\Database\Factories\PermissionFactory;
use App\Modules\Role\Infrastructure\Database\Factories\RoleFactory;
use App\Modules\User\Infrastructure\Database\Factories\UserFactory;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Database Seeder using Factories.
 */
class DatabaseSeeder
{
    public function run(): void
    {
        $connection = Capsule::connection();
        $driver = $connection->getDriverName();

        // Disable foreign key checks (MySQL/MariaDB only)
        if (in_array($driver, ['mysql', 'mariadb'])) {
            Capsule::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Truncate tables
        Capsule::table('role_user')->truncate();
        Capsule::table('permission_role')->truncate();
        \App\Modules\Permission\Infrastructure\Models\Permission::truncate();
        \App\Modules\Role\Infrastructure\Models\Role::truncate();
        \App\Modules\User\Infrastructure\Models\User::truncate();

        // Re-enable foreign key checks (MySQL/MariaDB only)
        if (in_array($driver, ['mysql', 'mariadb'])) {
            Capsule::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Create roles
        $adminRole = (new RoleFactory())->create(['name' => 'admin']);
        $managerRole = (new RoleFactory())->create(['name' => 'manager']);
        $userRole = (new RoleFactory())->create(['name' => 'user']);

        // Create permissions
        $actions = ['list', 'show', 'create', 'update', 'delete'];
        $permissions = [];

        foreach (['admin', 'manager', 'user'] as $roleName) {
            foreach ($actions as $action) {
                $permName = "{$action}-{$roleName}";
                $permission = (new PermissionFactory())->create(['name' => $permName]);
                $permissions[$permName] = $permission;

                // Attach permission to matching role
                $role = match ($roleName) {
                    'admin' => $adminRole,
                    'manager' => $managerRole,
                    'user' => $userRole,
                };
                $role->permissions()->attach($permission->id);
            }
        }

        // Create users with roles
        $adminUser = (new UserFactory())->create([
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
        ]);
        $adminUser->roles()->attach($adminRole->id);

        $managerUser = (new UserFactory())->create([
            'name' => 'Manager User',
            'email' => 'manager@demo.com',
        ]);
        $managerUser->roles()->attach($managerRole->id);

        $regularUser = (new UserFactory())->create([
            'name' => 'Regular User',
            'email' => 'user@demo.com',
        ]);
        $regularUser->roles()->attach($userRole->id);

        // Create additional fake users
        (new UserFactory())->createMany(10);

        echo "✅ Seeded roles, permissions and users successfully using factories.\n";
    }
}
