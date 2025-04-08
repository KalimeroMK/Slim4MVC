<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

final class ListPermissionAction
{
    public function execute(): Collection
    {
        return Role::with('permissions')->get();
    }
}