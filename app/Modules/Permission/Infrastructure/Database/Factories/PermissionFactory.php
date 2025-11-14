<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Database\Factories;

use App\Modules\Core\Infrastructure\Database\Factories\Factory;
use App\Modules\Permission\Infrastructure\Models\Permission;
use Carbon\Carbon;

/**
 * Permission Factory for generating fake permission data.
 */
class PermissionFactory extends Factory
{
    /**
     * Create a permission with a specific name pattern.
     */
    public function withName(string $name): self
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
        ]);
    }

    protected function model(): string
    {
        return Permission::class;
    }

    protected function definition(): array
    {
        $actions = ['create', 'read', 'update', 'delete', 'list', 'show'];
        $resources = ['users', 'roles', 'permissions', 'products', 'orders', 'posts'];

        return [
            'name' => $this->faker()->randomElement($actions).'-'.$this->faker()->randomElement($resources),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
