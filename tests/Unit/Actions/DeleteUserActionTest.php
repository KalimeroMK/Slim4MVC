<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\User\DeleteUserAction;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class DeleteUserActionTest extends TestCase
{
    private DeleteUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new UserRepository();
        $this->action = new DeleteUserAction($repository);
    }

    public function test_execute_deletes_user_from_database(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $userId = $user->id;
        $this->action->execute($userId);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertNull(User::find($userId));
    }

    public function test_execute_throws_exception_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->execute(999);
    }
}
