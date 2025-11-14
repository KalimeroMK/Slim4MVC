<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
    ];

    protected $hidden = [];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Role\Infrastructure\Models\Role::class, 'permission_role');
    }
}
