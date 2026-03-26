<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Models;

use App\Modules\Core\Infrastructure\Database\Eloquent\AutoEloquentRelations;
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
 *
 * Auto Eager Loading Examples:
 *   - protected array $autoWith = ['roles'];        // Auto-load roles
 *   - protected array $excludeAutoWith = ['logs']; // Exclude logs
 *   - User::queryWithoutAutoWith()->find(1);       // Skip auto-loading
 *   - preload($users, ['roles', 'permissions']);   // Manual preload
 */
class User extends Model
{
    use AutoEloquentRelations;

    /**
     * Relations to auto eager load.
     * Set to empty array or remove property to disable for this model.
     *
     * @var list<string>
     */
    protected array $autoWith = ['roles'];

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
     * Get all unique permissions through roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Permission\Infrastructure\Models\Permission>
     */
    public function permissions(): \Illuminate\Database\Eloquent\Collection
    {
        $permissionIds = [];
        
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissionIds[$permission->id] = $permission;
            }
        }
        
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Permission\Infrastructure\Models\Permission> $collection */
        $collection = \Illuminate\Database\Eloquent\Collection::make(array_values($permissionIds));
        
        return $collection;
    }

    /**
     * Override save method to auto-assign user role if no roles assigned.
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
                // Find or create user role
                /** @phpstan-ignore-next-line */
                $userRole = Role::firstOrCreate(['name' => 'user']);

                // Insert role_user relationship directly
                $connection->table('role_user')->insert([
                    'user_id' => $this->id,
                    'role_id' => $userRole->id,
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
