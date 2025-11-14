<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Role;

class RoleResource extends Resource
{
    /**
     * Transform the role into an array.
     */
    public static function make(mixed $resource): array
    {
        if (! ($resource instanceof Role)) {
            return [];
        }

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
