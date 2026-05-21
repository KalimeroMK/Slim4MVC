<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\LogoutAction;
use App\Modules\Core\Infrastructure\Support\AdvancedJwtServiceInterface as AdvancedJwtService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LogoutActionTest extends TestCase
{
    /** @var AdvancedJwtService&MockObject */
    private MockObject $jwtService;

    private LogoutAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = $this->createMock(AdvancedJwtService::class);
        $this->action = new LogoutAction($this->jwtService);
    }

    public function test_revokes_token_when_jti_provided(): void
    {
        $this->jwtService->expects($this->once())
            ->method('revokeRefreshToken')
            ->with('test-jti-123');

        $this->action->execute('test-jti-123');
    }

    public function test_does_nothing_when_jti_is_null(): void
    {
        $this->jwtService->expects($this->never())->method('revokeRefreshToken');

        $this->action->execute(null);
    }
}
