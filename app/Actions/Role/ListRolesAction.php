<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

final class ListRolesAction
{
    public function execute(): Collection
    {
        return Role::with('permissions')->get();
    }
}
