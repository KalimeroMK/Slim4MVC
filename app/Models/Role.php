<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
    ];

    protected $hidden = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

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
