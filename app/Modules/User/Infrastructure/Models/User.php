<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Models;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Observers\UserObserver;
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
 * @property \Illuminate\Database\Eloquent\Collection<int, Role> $roles
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
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    /**
     * Check if user has the given role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has the given permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }

    /**
     * Override save method to auto-assign client role.
     *
     * @param array<string, mixed> $options
     */
    public function save(array $options = []): bool
    {
        $isNew = ! $this->exists;

        $saved = parent::save($options);

        // If new user was created, assign client role
        if ($saved && $isNew) {
            // Use the connection directly instead of DB facade
            $connection = $this->getConnection();

            // Check if user already has any roles
            $hasRoles = $connection->table('role_user')
                ->where('user_id', $this->id)
                ->exists();

            if (! $hasRoles) {
                // Find or create client role
                /** @phpstan-ignore-next-line */
                $clientRole = Role::firstOrCreate(['name' => 'client']);

                // Insert role_user relationship directly
                $connection->table('role_user')->insert([
                    'user_id' => $this->id,
                    'role_id' => $clientRole->id,
                ]);
            }
        }

        return $saved;
    }

    /**
     * Boot the model and register observers.
     */
    protected static function booted(): void
    {
        static::observe(UserObserver::class);
    }
}
