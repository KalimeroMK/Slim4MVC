<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function test_not_found_exception_has_correct_code(): void
    {
        $exception = new NotFoundException('Test message');

        $this->assertEquals(404, $exception->getCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_unauthorized_exception_has_correct_code(): void
    {
        $exception = new UnauthorizedException('Test message');

        $this->assertEquals(401, $exception->getCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_forbidden_exception_has_correct_code(): void
    {
        $exception = new ForbiddenException('Test message');

        $this->assertEquals(403, $exception->getCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_bad_request_exception_has_correct_code(): void
    {
        $exception = new BadRequestException('Test message');

        $this->assertEquals(400, $exception->getCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_invalid_credentials_exception_has_correct_code(): void
    {
        $exception = new InvalidCredentialsException('Test message');

        $this->assertEquals(401, $exception->getCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }
}

