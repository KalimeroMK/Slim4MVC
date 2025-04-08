<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

// Setup Eloquent
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
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Disable foreign key checks
Capsule::statement('SET FOREIGN_KEY_CHECKS=0;');

Capsule::table('role_user')->truncate();
Capsule::table('permission_role')->truncate();
Permission::truncate();
Role::truncate();
User::truncate();

// Re-enable foreign key checks
Capsule::statement('SET FOREIGN_KEY_CHECKS=1;');

// Define roles
$roles = ['admin', 'manager', 'user'];
$roleInstances = [];

foreach ($roles as $roleName) {
    $roleInstances[$roleName] = Role::create(['name' => $roleName]);
}

// Define actions
$actions = ['list', 'show', 'create', 'update', 'delete'];
$permissionInstances = [];

// Create permissions per role
foreach ($roles as $roleName) {
    foreach ($actions as $action) {
        $permName = "{$action}-{$roleName}";
        $permission = Permission::create(['name' => $permName]);
        $permissionInstances[$permName] = $permission;

        // Attach permission to matching role
        $roleInstances[$roleName]->permissions()->attach($permission->id);
    }
}

// Create users and assign roles
$users = [
    ['name' => 'Admin User', 'email' => 'admin@demo.com', 'password' => 'password', 'role' => 'admin'],
    ['name' => 'Manager User', 'email' => 'manager@demo.com', 'password' => 'password', 'role' => 'manager'],
    ['name' => 'Regular User', 'email' => 'user@demo.com', 'password' => 'password', 'role' => 'user'],
];

foreach ($users as $u) {
    $user = User::create([
        'name' => $u['name'],
        'email' => $u['email'],
        'password' => password_hash($u['password'], PASSWORD_BCRYPT),
    ]);

    $user->roles()->attach($roleInstances[$u['role']]->id);
}

echo "✅ Seeded roles, permissions and users successfully.\n";
