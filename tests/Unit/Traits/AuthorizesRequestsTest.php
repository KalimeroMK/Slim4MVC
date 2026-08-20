<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Modules\Core\Infrastructure\Support\AuthHelper;
use App\Modules\Core\Infrastructure\Traits\AuthorizesRequests;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\CoversTrait;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/**
 * A model deliberately outside the Infrastructure\Models namespace, so the
 * convention-based policy lookup finds nothing for it.
 */
final class PolicylessThing extends Model
{
    protected $table = 'roles';
}

final class AuthorizesRequestsProbe
{
    use AuthorizesRequests;

    public function callAuthorize(string $ability, mixed $resource = null): bool
    {
        return $this->authorize($ability, $resource);
    }

    public function callCan(string $permission): bool
    {
        return $this->can($permission);
    }

    public function callHasRole(string $role): bool
    {
        return $this->hasRole($role);
    }

    public function callUnauthorized(?string $message = null): ResponseInterface
    {
        return $this->unauthorized($message);
    }
}

#[CoversTrait(AuthorizesRequests::class)]
final class AuthorizesRequestsTest extends TestCase
{
    private AuthorizesRequestsProbe $probe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->probe = new AuthorizesRequestsProbe();
    }

    // ------------------------------------------------------------ can / hasRole

    public function test_can_reflects_the_session_permissions(): void
    {
        AuthHelper::setUser(['id' => 1, 'permissions' => ['edit-roles']]);

        $this->assertTrue($this->probe->callCan('edit-roles'));
        $this->assertFalse($this->probe->callCan('delete-roles'));
    }

    public function test_has_role_reflects_the_session_roles(): void
    {
        AuthHelper::setUser(['id' => 1, 'roles' => ['admin']]);

        $this->assertTrue($this->probe->callHasRole('admin'));
        $this->assertFalse($this->probe->callHasRole('super-admin'));
    }

    public function test_a_guest_has_no_permissions_or_roles(): void
    {
        $this->assertFalse($this->probe->callCan('edit-roles'));
        $this->assertFalse($this->probe->callHasRole('admin'));
    }

    public function test_unauthorized_returns_401_with_the_given_message(): void
    {
        $default = $this->probe->callUnauthorized();
        $custom = $this->probe->callUnauthorized('Token expired');

        $this->assertSame(401, $default->getStatusCode());
        $this->assertSame('Unauthorized', json_decode((string) $default->getBody(), true)['message']);
        $this->assertSame('Token expired', json_decode((string) $custom->getBody(), true)['message']);
    }

    // ---------------------------------------------------------------- authorize

    public function test_authorize_denies_a_guest(): void
    {
        $this->assertFalse($this->probe->callAuthorize('create'));
    }

    public function test_authorize_denies_a_session_pointing_at_a_deleted_user(): void
    {
        // The session outlives the row it refers to.
        AuthHelper::setUser(['id' => 99999, 'permissions' => ['create-roles']]);

        $this->assertFalse($this->probe->callAuthorize('create'));
    }

    public function test_authorize_without_a_resource_falls_back_to_the_permission_check(): void
    {
        $user = $this->createUser(['email' => 'perm@example.com']);
        AuthHelper::setUser(['id' => $user->id, 'permissions' => ['create-roles']]);

        $this->assertTrue($this->probe->callAuthorize('create-roles'));
        $this->assertFalse($this->probe->callAuthorize('delete-roles'));
    }

    public function test_authorize_consults_the_policy_for_a_model(): void
    {
        $role = $this->createRole(['name' => 'editors']);
        $user = $this->grantPermission('edit-roles');

        // The permission lives on the user's role, not in the session, so a pass here
        // proves RolePolicy::update() was actually consulted.
        AuthHelper::setUser(['id' => $user->id]);

        $this->assertTrue($this->probe->callAuthorize('update', $role));
    }

    public function test_authorize_denies_when_the_policy_says_no(): void
    {
        $role = $this->createRole(['name' => 'viewers']);
        $user = $this->grantPermission('view-roles');
        AuthHelper::setUser(['id' => $user->id]);

        $this->assertTrue($this->probe->callAuthorize('view', $role));
        $this->assertFalse($this->probe->callAuthorize('delete', $role));
    }

    public function test_a_super_admin_bypasses_the_policy(): void
    {
        $role = $this->createRole(['name' => 'anything']);
        $user = $this->createUser(['email' => 'super@example.com']);
        $user->roles()->attach($this->createRole(['name' => 'super-admin'])->id);
        AuthHelper::setUser(['id' => $user->id]);

        // Policy::before() short-circuits before delete() is ever reached.
        $this->assertTrue($this->probe->callAuthorize('delete', $role));
    }

    public function test_authorize_denies_an_ability_the_policy_does_not_define(): void
    {
        $role = $this->createRole(['name' => 'partial']);
        $user = $this->grantPermission('edit-roles');
        AuthHelper::setUser(['id' => $user->id]);

        $this->assertFalse($this->probe->callAuthorize('teleport', $role));
    }

    public function test_a_model_without_a_policy_falls_back_to_the_permission_check(): void
    {
        $user = $this->createUser(['email' => 'nopolicy@example.com']);
        AuthHelper::setUser(['id' => $user->id, 'permissions' => ['edit-roles']]);

        $thing = new PolicylessThing();

        $this->assertTrue($this->probe->callAuthorize('edit-roles', $thing));
        $this->assertFalse($this->probe->callAuthorize('delete-roles', $thing));
    }

    /**
     * A user whose role carries the given permission.
     */
    private function grantPermission(string $permission): User
    {
        $user = $this->createUser(['email' => $permission.'@example.com']);
        $role = $this->createRole(['name' => 'granted-'.$permission]);
        $role->permissions()->attach($this->createPermission(['name' => $permission])->id);
        $user->roles()->attach($role->id);

        return $user;
    }
}
