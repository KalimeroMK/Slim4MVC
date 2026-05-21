<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\PasswordRecoveryAction;
use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Events\PasswordResetRequested;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class PasswordRecoveryActionTest extends TestCase
{
    private PasswordRecoveryAction $action;

    /** @var Dispatcher&MockObject */
    private MockObject $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->action = new PasswordRecoveryAction(new UserRepository(), $this->dispatcher);
    }

    public function test_stores_hashed_token_and_dispatches_event_with_raw_token(): void
    {
        $user = User::create([
            'name'     => 'Test User',
            'email'    => 'recovery@example.com',
            'password' => password_hash('pass', PASSWORD_BCRYPT),
        ]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (PasswordResetRequested $event) use ($user): bool {
                // Raw token must NOT be the same as the hash stored in the DB
                $this->assertNotEquals(hash('sha256', $event->token), $event->token);
                $this->assertSame($user->id, $event->user->id);
                return true;
            }));

        $this->action->execute(new PasswordRecoveryDTO('recovery@example.com'));

        $user->refresh();
        $this->assertNotNull($user->password_reset_token);
        // DB must store the hash, not the raw token
        $this->assertEquals(64, strlen((string) $user->password_reset_token));
        // Expiry must be ~1 hour from now
        $this->assertNotNull($user->password_reset_token_expires_at);
        $expiresAt = strtotime((string) $user->password_reset_token_expires_at);
        $this->assertGreaterThan(time() + 3500, $expiresAt);
        $this->assertLessThanOrEqual(time() + 3600, $expiresAt);
    }

    public function test_silently_ignores_unknown_email(): void
    {
        $this->dispatcher->expects($this->never())->method('dispatch');

        // Must not throw
        $this->action->execute(new PasswordRecoveryDTO('nobody@example.com'));
    }
}
