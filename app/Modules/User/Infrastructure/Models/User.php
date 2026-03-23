<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $password_reset_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Role\Infrastructure\Models\Role> $roles
 *
 * @method static static|null find(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<self> where(string $column, mixed $operator = null, mixed $value = null)
 */
class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_reset_token',
        'email_verified_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['password'];

    /**
     * @return BelongsToMany<\App\Modules\Role\Infrastructure\Models\Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Role\Infrastructure\Models\Role::class, 'role_user');
    }

    /**
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function permissions(): \Illuminate\Support\Collection
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->contains('name', $permission);
    }
}
