<?php

declare(strict_types=1);

namespace Database\Seed;

use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;
use Closure;

class DatabaseSeeder
{
    /**
     * @param  Closure(string):void|null  $output  Where progress is reported. Silent when
     *                                             omitted, so tests do not write to stdout.
     */
    public function __construct(private readonly ?Closure $output = null) {}

    public function run(): void
    {
        $this->write('Seeding database...');

        // Create roles (firstOrCreate for idempotency)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $this->write('✅ Roles created');

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
        $this->write('✅ Permissions created');

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
        $this->write('✅ Permissions assigned to roles');

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

        $this->write('✅ Users created with roles');
        $this->write('');
        $this->write('Login credentials:');
        $this->write('Admin: admin@demo.com / password');
        $this->write('Manager: manager@demo.com / password');
        $this->write('User: user@demo.com / password');
    }

    private function write(string $message): void
    {
        if ($this->output instanceof Closure) {
            ($this->output)($message);
        }
    }
}
