<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\ResetPasswordAction;
use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Tests\TestCase;

final class ResetPasswordActionTest extends TestCase
{
    private ResetPasswordAction $resetPasswordAction;

    protected function setUp(): void
    {
        parent::setUp();
        $userRepository = new UserRepository();
        $this->resetPasswordAction = new ResetPasswordAction($userRepository);
    }

    public function test_execute_with_valid_token_resets_password(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('oldpassword', PASSWORD_BCRYPT),
            'password_reset_token' => 'valid-token-123',
        ]);

        $resetPasswordDTO = new ResetPasswordDTO('valid-token-123', 'newpassword123');
        $this->resetPasswordAction->execute($resetPasswordDTO);

        $user = User::find($user->id); // Reload from database
        $this->assertTrue(password_verify('newpassword123', (string) $user->password));
        $this->assertNull($user->password_reset_token);
    }

    public function test_execute_with_invalid_token_throws_exception(): void
    {
        $resetPasswordDTO = new ResetPasswordDTO('invalid-token', 'newpassword123');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Invalid or expired reset token');

        $this->resetPasswordAction->execute($resetPasswordDTO);
    }

    public function test_execute_with_nonexistent_token_throws_exception(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('oldpassword', PASSWORD_BCRYPT),
            'password_reset_token' => 'different-token',
        ]);

        $resetPasswordDTO = new ResetPasswordDTO('nonexistent-token', 'newpassword123');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Invalid or expired reset token');

        $this->resetPasswordAction->execute($resetPasswordDTO);
    }
}
