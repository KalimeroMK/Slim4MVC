<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Observers;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Automatically assign "client" role if no roles are assigned.
     */
    public function created(User $user): void
    {
        error_log("UserObserver: created event triggered for user ID: " . $user->id);
        
        try {
            // Check if user already has roles
            $hasRoles = \DB::table('role_user')
                ->where('user_id', $user->id)
                ->exists();
            
            error_log("UserObserver: User has roles: " . ($hasRoles ? 'yes' : 'no'));
            
            if (! $hasRoles) {
                // Find or create client role
                $clientRole = Role::firstOrCreate(['name' => 'client']);
                error_log("UserObserver: Client role ID: " . $clientRole->id);
                
                // Assign client role to user
                \DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => $clientRole->id,
                ]);
                
                error_log("UserObserver: Client role assigned successfully");
            }
        } catch (\Exception $e) {
            error_log("UserObserver Error: " . $e->getMessage());
        }
    }
}
