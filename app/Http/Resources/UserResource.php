<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;

class UserResource extends Resource
{
    /**
     * Transform the user into an array.
     */
    public static function make(mixed $resource): array
    {
        if (!($resource instanceof User)) {
            return [];
        }

        $user = $resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'roles' => $user->relationLoaded('roles')
                ? $user->roles->pluck('name')->toArray()
                : [],
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}

