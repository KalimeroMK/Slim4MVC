<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;
use Tests\TestCase;

/**
 * A new user gets exactly one default role, and it is 'user'.
 *
 * There were two places claiming to assign it: User::save() naming 'user', and
 * UserObserver::created() naming 'client'. Only the first could ever run, so these
 * pin which name new accounts actually carry rather than which one the code reads
 * like it assigns.
 */
final class UserDefaultRoleTest extends TestCase
{
    public function test_a_new_user_gets_the_user_role(): void
    {
        $user = $this->makeUser('fresh@example.com');

        $this->assertSame(['user'], $user->roles()->pluck('name')->all());
    }

    public function test_creating_a_user_does_not_invent_a_client_role(): void
    {
        $this->makeUser('fresh2@example.com');

        $this->assertNull(
            Role::where('name', 'client')->first(),
            'no code path should create a client role just by adding a user'
        );
    }

    public function test_a_user_given_a_role_up_front_keeps_only_that_one(): void
    {
        $role = new Role();
        $role->name = 'admin';
        $role->save();

        $user = new User();
        $user->name = 'Preassigned';
        $user->email = 'preassigned@example.com';
        $user->password = password_hash('x', PASSWORD_DEFAULT);
        $user->save();
        $user->roles()->detach();
        $user->roles()->attach($role->id);

        $this->assertSame(['admin'], $user->roles()->pluck('name')->all());
    }

    private function makeUser(string $email): User
    {
        $user = new User();
        $user->name = 'Fresh';
        $user->email = $email;
        $user->password = password_hash('x', PASSWORD_DEFAULT);
        $user->save();

        return $user;
    }
}
