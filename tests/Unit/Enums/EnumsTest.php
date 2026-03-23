<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Modules\Core\Application\Enums\ApiResponseStatus;
use App\Modules\Core\Application\Enums\HttpStatusCode;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_api_response_status_enum_exists(): void
    {
        $this->assertTrue(enum_exists(ApiResponseStatus::class));
    }

    public function test_http_status_code_enum_exists(): void
    {
        $this->assertTrue(enum_exists(HttpStatusCode::class));
    }

    public function test_api_response_status_has_success_case(): void
    {
        $this->assertTrue(ApiResponseStatus::tryFrom('success') !== null);
    }

    public function test_api_response_status_has_error_case(): void
    {
        $this->assertTrue(ApiResponseStatus::tryFrom('error') !== null);
    }

    public function test_http_status_code_has_ok_case(): void
    {
        $this->assertTrue(HttpStatusCode::tryFrom(200) !== null);
    }

    public function test_http_status_code_has_not_found_case(): void
    {
        $this->assertTrue(HttpStatusCode::tryFrom(404) !== null);
    }

    public function test_http_status_code_has_server_error_case(): void
    {
        $this->assertTrue(HttpStatusCode::tryFrom(500) !== null);
    }
}
