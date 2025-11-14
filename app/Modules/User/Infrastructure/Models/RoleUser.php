<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    protected $fillable = [
        'role_id',
        'user_id',
    ];

    protected $hidden = [];
}
