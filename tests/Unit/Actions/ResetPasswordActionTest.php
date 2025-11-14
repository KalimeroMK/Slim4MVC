<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Auth\ResetPasswordAction;
use App\DTO\Auth\ResetPasswordDTO;
use App\Models\User;
use RuntimeException;
use Tests\TestCase;

class ResetPasswordActionTest extends TestCase
{
    private ResetPasswordAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ResetPasswordAction();
    }

    public function test_execute_with_valid_token_resets_password(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('oldpassword', PASSWORD_BCRYPT),
            'password_reset_token' => 'valid-token-123',
        ]);

        $dto = new ResetPasswordDTO('valid-token-123', 'newpassword123');
        $this->action->execute($dto);

        $user = User::find($user->id); // Reload from database
        $this->assertTrue(password_verify('newpassword123', $user->password));
        $this->assertNull($user->password_reset_token);
    }

    public function test_execute_with_invalid_token_throws_exception(): void
    {
        $dto = new ResetPasswordDTO('invalid-token', 'newpassword123');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid or expired reset token');

        $this->action->execute($dto);
    }

    public function test_execute_with_nonexistent_token_throws_exception(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('oldpassword', PASSWORD_BCRYPT),
            'password_reset_token' => 'different-token',
        ]);

        $dto = new ResetPasswordDTO('nonexistent-token', 'newpassword123');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid or expired reset token');

        $this->action->execute($dto);
    }
}
