<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Modules\Auth\Application\Actions\Auth\RefreshTokenAction;
use App\Modules\Core\Infrastructure\Support\AdvancedJwtServiceInterface as AdvancedJwtService;
use App\Modules\Core\Infrastructure\Support\Token\TokenPair;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RefreshTokenActionTest extends TestCase
{
    public function test_returns_new_token_pair_on_valid_refresh_token(): void
    {
        $tokenPair = new TokenPair('new-access', 'new-refresh', 2592000);

        /** @var AdvancedJwtService&MockObject $jwtService */
        $jwtService = $this->createMock(AdvancedJwtService::class);
        $jwtService->expects($this->once())
            ->method('rotateRefreshToken')
            ->with('old-refresh-token')
            ->willReturn($tokenPair);

        $result = (new RefreshTokenAction($jwtService))->execute('old-refresh-token');

        $this->assertSame($tokenPair, $result);
    }

    public function test_propagates_exception_on_invalid_token(): void
    {
        $stub = $this->createStub(AdvancedJwtService::class);
        $stub->method('rotateRefreshToken')
            ->willThrowException(new RuntimeException('Refresh token has been revoked'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refresh token has been revoked');

        (new RefreshTokenAction($stub))->execute('revoked-token');
    }
}
