<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionRole extends Model
{
    protected $table = 'permission_role';

    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    protected $hidden = [];
}
