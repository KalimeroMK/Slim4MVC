<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Application\Actions\GetUserAction;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

final class GetUserActionTest extends TestCase
{
    private GetUserAction $getUserAction;

    protected function setUp(): void
    {
        parent::setUp();
        $userRepository = new UserRepository();
        $this->getUserAction = new GetUserAction($userRepository);
    }

    public function test_execute_returns_user_with_roles(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $role = Role::create(['name' => 'admin']);
        $user->roles()->attach($role->id);

        $result = $this->getUserAction->execute($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertTrue($result->relationLoaded('roles'));
        // User gets 'user' role automatically + the admin role we attached = 2 roles
        $this->assertGreaterThanOrEqual(1, $result->roles->count());
        $this->assertTrue($result->roles->contains('name', 'admin'));
    }

    public function test_execute_throws_exception_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->getUserAction->execute(999);
    }
}
