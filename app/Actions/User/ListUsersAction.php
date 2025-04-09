<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ListUsersAction
{
    public function execute(): Collection
    {
        return User::with('roles')->get();
    }
}
