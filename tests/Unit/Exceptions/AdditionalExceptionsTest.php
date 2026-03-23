<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Modules\Core\Infrastructure\Exceptions\BadRequestException;
use App\Modules\Core\Infrastructure\Exceptions\ForbiddenException;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Exceptions\UnauthorizedException;
use App\Modules\Core\Infrastructure\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AdditionalExceptionsTest extends TestCase
{
    public function test_bad_request_exception_exists(): void
    {
        $this->assertTrue(class_exists(BadRequestException::class));
    }

    public function test_forbidden_exception_exists(): void
    {
        $this->assertTrue(class_exists(ForbiddenException::class));
    }

    public function test_invalid_credentials_exception_exists(): void
    {
        $this->assertTrue(class_exists(InvalidCredentialsException::class));
    }

    public function test_not_found_exception_exists(): void
    {
        $this->assertTrue(class_exists(NotFoundException::class));
    }

    public function test_unauthorized_exception_exists(): void
    {
        $this->assertTrue(class_exists(UnauthorizedException::class));
    }

    public function test_validation_exception_exists(): void
    {
        $this->assertTrue(class_exists(ValidationException::class));
    }

    public function test_exceptions_extend_runtime_exception(): void
    {
        $exceptions = [
            new BadRequestException('test'),
            new ForbiddenException('test'),
            new InvalidCredentialsException('test'),
            new NotFoundException('test'),
            new UnauthorizedException('test'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(RuntimeException::class, $exception);
        }
    }
}
