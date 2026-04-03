<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\User\Application\Actions\DeleteUserAction;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

final class DeleteUserActionTest extends TestCase
{
    private DeleteUserAction $deleteUserAction;

    protected function setUp(): void
    {
        parent::setUp();
        $userRepository = new UserRepository();
        $this->deleteUserAction = new DeleteUserAction($userRepository);
    }

    public function test_execute_deletes_user_from_database(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ]);

        $userId = $user->id;
        $this->deleteUserAction->execute($userId);

        // Soft delete: record remains in DB with deleted_at set
        $this->assertDatabaseHas('users', ['id' => $userId]);
        $this->assertNotNull(User::withTrashed()->find($userId)?->deleted_at);
        // Regular find() returns null for soft-deleted records
        $this->assertNull(User::find($userId));
    }

    public function test_execute_throws_exception_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->deleteUserAction->execute(999);
    }
}
