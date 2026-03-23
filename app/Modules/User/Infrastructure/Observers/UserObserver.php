<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Observers;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Automatically assign "client" role if no roles are assigned.
     */
    public function created(User $user): void
    {
        $hasRoles = DB::table('role_user')
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasRoles) {
            /** @phpstan-ignore-next-line */
            $clientRole = Role::firstOrCreate(['name' => 'client']);

            DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => $clientRole->id,
            ]);
        }
    }
}
