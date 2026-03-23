<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Resources;

use App\Modules\Role\Infrastructure\Models\Role;

/**
 * @phpstan-ignore-next-line
 */
class RoleResource extends \App\Modules\Core\Infrastructure\Http\Resources\Resource
{
    /**
     * Transform the role into an array.
     *
     * @param  Role  $resource
     * @return array<string, mixed>
     */
    public static function make(mixed $resource): array
    {
        /** @var Role $role */
        $role = $resource;

        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->relationLoaded('permissions')
                ? $role->permissions->pluck('name')->toArray()
                : [],
            'created_at' => $role->created_at?->toIso8601String(),
            'updated_at' => $role->updated_at?->toIso8601String(),
        ];
    }
}
