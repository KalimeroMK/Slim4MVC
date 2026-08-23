<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Core\Infrastructure\Support\AuthHelper;
use App\Modules\Core\Infrastructure\Support\SessionAuth;
use App\Modules\Permission\Infrastructure\Models\Permission;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\TestCase;

/**
 * A web login has to leave the session in the state AuthHelper reads back.
 *
 * AuthHelper::setUser() takes roles and permissions out of the array it is handed,
 * so anything the login does not put in there is simply absent afterwards - and
 * hasRole()/can() answer false for a user who genuinely holds the role.
 */
final class SessionAuthRolesTest extends TestCase
{
    private const PASSWORD = 'correct-horse-battery-staple';

    public function test_it_authenticates_with_the_right_password(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($this->login($user));
        $this->assertSame($user->id, $_SESSION['user_id']);
    }

    public function test_it_rejects_the_wrong_password(): void
    {
        $this->makeUser();
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $this->assertFalse((new SessionAuth($session))->attempt('role-holder@example.com', 'wrong'));
    }

    public function test_it_carries_roles_into_the_session(): void
    {
        $user = $this->makeUser();
        $role = new Role();
        $role->name = 'admin';
        $role->save();
        $user->roles()->attach($role->id);

        $this->assertTrue($this->login($user));

        // User::save() attaches a default 'user' role to every new user, so the
        // expectation here is containment rather than an exact list.
        $this->assertContains('admin', $_SESSION['user_roles']);
        $this->assertTrue(AuthHelper::hasRole('admin'), 'a user holding the role must pass hasRole()');
    }

    public function test_it_carries_permissions_into_the_session(): void
    {
        $user = $this->makeUser();
        $role = new Role();
        $role->name = 'editor';
        $role->save();

        $permission = new Permission();
        $permission->name = 'posts.edit';
        $permission->save();

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $this->assertTrue($this->login($user));

        $this->assertSame(['posts.edit'], $_SESSION['user_permissions']);
        $this->assertTrue(AuthHelper::can('posts.edit'), 'a permission the role grants must pass can()');
    }

    public function test_it_reports_only_the_roles_a_user_actually_holds(): void
    {
        $user = $this->makeUser();
        $unrelated = new Role();
        $unrelated->name = 'admin';
        $unrelated->save();

        $this->assertTrue($this->login($user));

        // The default 'user' role from User::save() is present; a role that exists in the
        // table but was never attached must not be.
        $this->assertSame(['user'], $_SESSION['user_roles']);
        $this->assertSame([], $_SESSION['user_permissions']);
        $this->assertFalse(AuthHelper::hasRole('admin'));
    }

    private function login(User $user): bool
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        return (new SessionAuth($session))->attempt((string) $user->email, self::PASSWORD);
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->name = 'Role Holder';
        $user->email = 'role-holder@example.com';
        $user->password = password_hash(self::PASSWORD, PASSWORD_DEFAULT);
        $user->save();

        return $user;
    }
}
