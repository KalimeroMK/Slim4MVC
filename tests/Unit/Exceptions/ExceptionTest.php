<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Modules\Core\Infrastructure\Exceptions\BadRequestException;
use App\Modules\Core\Infrastructure\Exceptions\ForbiddenException;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    public function test_not_found_exception_has_correct_code(): void
    {
        $notFoundException = new NotFoundException('Test message');

        $this->assertEquals(404, $notFoundException->getCode());
        $this->assertSame('Test message', $notFoundException->getMessage());
    }

    public function test_unauthorized_exception_has_correct_code(): void
    {
        $unauthorizedException = new UnauthorizedException('Test message');

        $this->assertEquals(401, $unauthorizedException->getCode());
        $this->assertSame('Test message', $unauthorizedException->getMessage());
    }

    public function test_forbidden_exception_has_correct_code(): void
    {
        $forbiddenException = new ForbiddenException('Test message');

        $this->assertEquals(403, $forbiddenException->getCode());
        $this->assertSame('Test message', $forbiddenException->getMessage());
    }

    public function test_bad_request_exception_has_correct_code(): void
    {
        $badRequestException = new BadRequestException('Test message');

        $this->assertEquals(400, $badRequestException->getCode());
        $this->assertSame('Test message', $badRequestException->getMessage());
    }

    public function test_invalid_credentials_exception_has_correct_code(): void
    {
        $invalidCredentialsException = new InvalidCredentialsException('Test message');

        $this->assertEquals(401, $invalidCredentialsException->getCode());
        $this->assertSame('Test message', $invalidCredentialsException->getMessage());
    }
}
