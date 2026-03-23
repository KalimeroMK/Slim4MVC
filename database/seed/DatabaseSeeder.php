<?php

declare(strict_types=1);

namespace Database\Seed;

use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;

class DatabaseSeeder
{
    public function run(): void
    {
        echo "Seeding database...\n";

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $clientRole = Role::create(['name' => 'client']);
        $managerRole = Role::create(['name' => 'manager']);
        echo "✅ Roles created\n";

        // Create permissions
        $permissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
        ];

        foreach ($permissions as $permName) {
            Permission::create(['name' => $permName]);
        }
        echo "✅ Permissions created\n";

        // Assign all permissions to admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Assign view permissions to client
        $clientRole->permissions()->sync(
            Permission::where('name', 'like', 'view-%')->pluck('id')
        );

        // Assign view and edit permissions to manager
        $managerRole->permissions()->sync(
            Permission::where('name', 'like', 'view-%')
                ->orWhere('name', 'like', 'edit-%')
                ->pluck('id')
        );
        echo "✅ Permissions assigned to roles\n";

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);
        $admin->roles()->sync([$adminRole->id]);

        // Create client user
        $client = User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);
        $client->roles()->sync([$clientRole->id]);

        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ]);
        $manager->roles()->sync([$managerRole->id]);

        echo "✅ Users created with roles\n";
        echo "\nLogin credentials:\n";
        echo "Admin: admin@example.com / password123\n";
        echo "Client: client@example.com / password123\n";
        echo "Manager: manager@example.com / password123\n";
    }
}
