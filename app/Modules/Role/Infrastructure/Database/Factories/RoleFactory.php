<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Database\Factories;

use App\Modules\Core\Infrastructure\Database\Factories\Factory;
use App\Modules\Role\Infrastructure\Models\Role;
use Carbon\Carbon;

/**
 * Role Factory for generating fake role data.
 *
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Create a role with specific permissions.
     *
     * @param  list<string|int>  $permissions  Permission names or IDs
     */
    public function withPermissions(array $permissions): Role
    {
        /** @var Role $model */
        $model = $this->create();
        $permissionIds = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                /** @var \App\Modules\Permission\Infrastructure\Models\Permission|null $permissionModel */
                $permissionModel = \App\Modules\Permission\Infrastructure\Models\Permission::where('name', $permission)->first();
                if ($permissionModel) {
                    $permissionIds[] = $permissionModel->id;
                }
            } else {
                $permissionIds[] = $permission;
            }
        }

        if ($permissionIds !== []) {
            $model->permissions()->attach($permissionIds);
        }

        return $model->fresh() ?? $model;
    }

    protected function model(): string
    {
        return Role::class;
    }

    protected function definition(): array
    {
        return [
            'name' => $this->faker()->unique()->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
