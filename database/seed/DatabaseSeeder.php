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

        // Create roles (firstOrCreate for idempotency)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        echo "✅ Roles created\n";

        // Create permissions (firstOrCreate for idempotency)
        $permissions = [
            'list-admin',
            'create-admin',
            'edit-admin',
            'delete-admin',
            'list-manager',
            'create-manager',
            'edit-manager',
            'delete-manager',
            'list-user',
            'create-user',
            'edit-user',
            'delete-user',
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }
        echo "✅ Permissions created\n";

        // Assign all permissions to admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Assign view and edit permissions to manager
        $managerRole->permissions()->sync(
            Permission::where('name', 'like', 'list-%')
                ->orWhere('name', 'like', 'edit-%')
                ->pluck('id')
        );

        // Assign view permissions to user
        $userRole->permissions()->sync(
            Permission::where('name', 'like', 'list-%')->pluck('id')
        );

        // Assign view permissions to client
        $clientRole->permissions()->sync(
            Permission::where('name', 'like', 'view-%')->pluck('id')
        );
        echo "✅ Permissions assigned to roles\n";

        // Create admin user (firstOrCreate for idempotency)
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Admin User',
                'password' => password_hash('password', PASSWORD_BCRYPT),
            ]
        );
        $admin->roles()->sync([$adminRole->id]);

        // Create manager user (firstOrCreate for idempotency)
        $manager = User::firstOrCreate(
            ['email' => 'manager@demo.com'],
            [
                'name' => 'Manager User',
                'password' => password_hash('password', PASSWORD_BCRYPT),
            ]
        );
        $manager->roles()->sync([$managerRole->id]);

        // Create regular user (firstOrCreate for idempotency)
        $user = User::firstOrCreate(
            ['email' => 'user@demo.com'],
            [
                'name' => 'Regular User',
                'password' => password_hash('password', PASSWORD_BCRYPT),
            ]
        );
        $user->roles()->sync([$userRole->id]);

        // Create additional fake users (10 fake users)
        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 10; $i++) {
            $fakeUser = User::firstOrCreate(
                ['email' => 'fake'.$i.'@demo.com'],
                [
                    'name' => $faker->name(),
                    'password' => password_hash('password', PASSWORD_BCRYPT),
                ]
            );
            $fakeUser->roles()->sync([$userRole->id]);
        }

        echo "✅ Users created with roles\n";
        echo "\nLogin credentials:\n";
        echo "Admin: admin@demo.com / password\n";
        echo "Manager: manager@demo.com / password\n";
        echo "User: user@demo.com / password\n";
    }
}
