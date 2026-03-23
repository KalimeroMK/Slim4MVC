<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<self> where(string $column, mixed $operator = null, mixed $value = null)
 * @method static bool insert(array<string, mixed> $values)
 */
class PermissionRole extends Model
{
    protected $table = 'permission_role';

    /** @var list<string> */
    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    protected $hidden = [];
}
