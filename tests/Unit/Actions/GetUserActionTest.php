<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\User\GetUserAction;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class GetUserActionTest extends TestCase
{
    private GetUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new UserRepository();
        $this->action = new GetUserAction($repository);
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

        $result = $this->action->execute($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertTrue($result->relationLoaded('roles'));
        $this->assertCount(1, $result->roles);
    }

    public function test_execute_throws_exception_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->execute(999);
    }
}
