<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Resources;

use App\Modules\User\Infrastructure\Models\User;

/**
 * @phpstan-ignore-next-line
 */
class UserResource extends \App\Modules\Core\Infrastructure\Http\Resources\Resource
{
    /**
     * Transform the user into an array.
     *
     * @param  User  $resource
     * @return array<string, mixed>
     */
    public static function make(mixed $resource): array
    {
        /** @var User $user */
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
