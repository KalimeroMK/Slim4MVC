<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Permission\Infrastructure\Models\Permission> $permissions
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @method static static|null find(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<self> where(string $column, mixed $operator = null, mixed $value = null)
 * @method static static|null first()
 * @method static static create(array<string, mixed> $attributes)
 * @method void givePermissionTo(list<int>|list<string>|int|string $permissions)
 */
class Role extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    protected $hidden = [];

    /**
     * @return BelongsToMany<\App\Modules\User\Infrastructure\Models\User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\User\Infrastructure\Models\User::class, 'role_user');
    }

    /**
     * @return BelongsToMany<\App\Modules\Permission\Infrastructure\Models\Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Permission\Infrastructure\Models\Permission::class, 'permission_role');
    }

    /**
     * @param  list<int>|list<string>  $permissionIds
     */
    public function syncPermissions(array $permissionIds): void
    {
        PermissionRole::where('role_id', $this->id)->delete();

        foreach ($permissionIds as $permissionId) {
            PermissionRole::insert([
                'role_id' => $this->id,
                'permission_id' => $permissionId,
            ]);
        }
    }
}
