<?php

declare(strict_types=1);

/**
 * Script to update namespaces in module files
 */
$replacements = [
    // User module
    'App\\Actions\\User' => 'App\\Modules\\User\\Application\\Actions',
    'App\\DTO\\User' => 'App\\Modules\\User\\Application\\DTOs',
    'App\\Interface\\User' => 'App\\Modules\\User\\Application\\Interfaces',
    'App\\Models\\User' => 'App\\Modules\\User\\Infrastructure\\Models\\User',
    'App\\Repositories\\UserRepository' => 'App\\Modules\\User\\Infrastructure\\Repositories\\UserRepository',
    'App\\Http\\Controllers\\Api\\UserController' => 'App\\Modules\\User\\Infrastructure\\Http\\Controllers\\UserController',
    'App\\Http\\Requests\\User' => 'App\\Modules\\User\\Infrastructure\\Http\\Requests',
    'App\\Http\\Resources\\UserResource' => 'App\\Modules\\User\\Infrastructure\\Http\\Resources\\UserResource',
    'App\\Policies\\UserPolicy' => 'App\\Modules\\User\\Policies\\UserPolicy',

    // Role module
    'App\\Actions\\Role' => 'App\\Modules\\Role\\Application\\Actions',
    'App\\DTO\\Role' => 'App\\Modules\\Role\\Application\\DTOs',
    'App\\Interface\\Role' => 'App\\Modules\\Role\\Application\\Interfaces',
    'App\\Models\\Role' => 'App\\Modules\\Role\\Infrastructure\\Models\\Role',
    'App\\Repositories\\RoleRepository' => 'App\\Modules\\Role\\Infrastructure\\Repositories\\RoleRepository',
    'App\\Http\\Controllers\\Api\\RoleController' => 'App\\Modules\\Role\\Infrastructure\\Http\\Controllers\\RoleController',
    'App\\Http\\Requests\\Role' => 'App\\Modules\\Role\\Infrastructure\\Http\\Requests',
    'App\\Http\\Resources\\RoleResource' => 'App\\Modules\\Role\\Infrastructure\\Http\\Resources\\RoleResource',

    // Permission module
    'App\\Actions\\Permission' => 'App\\Modules\\Permission\\Application\\Actions',
    'App\\DTO\\Permission' => 'App\\Modules\\Permission\\Application\\DTOs',
    'App\\Interface\\Permission' => 'App\\Modules\\Permission\\Application\\Interfaces',
    'App\\Models\\Permission' => 'App\\Modules\\Permission\\Infrastructure\\Models\\Permission',
    'App\\Repositories\\PermissionRepository' => 'App\\Modules\\Permission\\Infrastructure\\Repositories\\PermissionRepository',
    'App\\Http\\Controllers\\Api\\PermissionController' => 'App\\Modules\\Permission\\Infrastructure\\Http\\Controllers\\PermissionController',
    'App\\Http\\Requests\\Permission' => 'App\\Modules\\Permission\\Infrastructure\\Http\\Requests',
    'App\\Http\\Resources\\PermissionResource' => 'App\\Modules\\Permission\\Infrastructure\\Http\\Resources\\PermissionResource',

    // Core module base classes
    'App\\Http\\Controllers\\Controller' => 'App\\Modules\\Core\\Infrastructure\\Http\\Controllers\\Controller',
    'App\\Policies\\Policy' => 'App\\Modules\\Core\\Infrastructure\\Policies\\Policy',
    'App\\Http\\Resources\\Resource' => 'App\\Modules\\Core\\Infrastructure\\Http\\Resources\\Resource',
    'App\\Repositories\\Repository' => 'App\\Modules\\Core\\Infrastructure\\Repositories\\Repository',
    'App\\Repositories\\EloquentRepository' => 'App\\Modules\\Core\\Infrastructure\\Repositories\\EloquentRepository',
    'App\\Http\\Requests\\FormRequest' => 'App\\Modules\\Core\\Infrastructure\\Http\\Requests\\FormRequest',
    'App\\Support\\ApiResponse' => 'App\\Modules\\Core\\Infrastructure\\Support\\ApiResponse',
    'App\\Support\\Auth' => 'App\\Modules\\Core\\Infrastructure\\Support\\Auth',
    'App\\Exceptions\\' => 'App\\Modules\\Core\\Infrastructure\\Exceptions\\',
    'App\\Enums\\' => 'App\\Modules\\Core\\Application\\Enums\\',
    'App\\Http\\Middleware\\' => 'App\\Modules\\Core\\Infrastructure\\Http\\Middleware\\',
    'App\\Traits\\' => 'App\\Modules\\Core\\Infrastructure\\Traits\\',
    'App\\Events\\' => 'App\\Modules\\Core\\Infrastructure\\Events\\',
    'App\\Listeners\\' => 'App\\Modules\\Core\\Infrastructure\\Listeners\\',
    'App\\Jobs\\' => 'App\\Modules\\Core\\Infrastructure\\Jobs\\',
    'App\\Queue\\' => 'App\\Modules\\Core\\Infrastructure\\Queue\\',
];

function updateFile(string $filePath, array $replacements): void
{
    $content = file_get_contents($filePath);
    $originalContent = $content;

    foreach ($replacements as $old => $new) {
        $content = str_replace($old, $new, $content);
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated: $filePath\n";
    }
}

function processDirectory(string $dir, array $replacements): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            updateFile($file->getPathname(), $replacements);
        }
    }
}

$projectRoot = __DIR__;
$modulesDir = "$projectRoot/app/Modules";

echo "Updating namespaces in modules...\n";
processDirectory($modulesDir, $replacements);
echo "Done!\n";
