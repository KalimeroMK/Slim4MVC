<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Role\Infrastructure\Models\Role> $roles
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static static|null find(int $id)
 * @method static static findOrFail(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<self> where(string $column, mixed $operator = null, mixed $value = null)
 * @method static static|null first()
 * @method static static create(array<string, mixed> $attributes)
 */
class Permission extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    protected $hidden = [];

    /**
     * @return BelongsToMany<\App\Modules\Role\Infrastructure\Models\Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Role\Infrastructure\Models\Role::class, 'permission_role');
    }
}
