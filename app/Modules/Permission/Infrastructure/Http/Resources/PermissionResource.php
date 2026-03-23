<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Http\Resources;

use App\Modules\Permission\Infrastructure\Models\Permission;

/**
 * @phpstan-ignore-next-line
 */
class PermissionResource extends \App\Modules\Core\Infrastructure\Http\Resources\Resource
{
    /**
     * Transform the permission into an array.
     *
     * @param  Permission  $resource
     * @return array<string, mixed>
     */
    public static function make(mixed $resource): array
    {
        /** @var Permission $permission */
        $permission = $resource;

        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'roles' => $permission->relationLoaded('roles')
                ? $permission->roles->pluck('name')->toArray()
                : [],
            'created_at' => $permission->created_at?->toIso8601String(),
            'updated_at' => $permission->updated_at?->toIso8601String(),
        ];
    }
}
