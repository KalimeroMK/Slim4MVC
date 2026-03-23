<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Modules\Core\Infrastructure\Support\AuthHelper;
use App\Modules\Core\Infrastructure\Support\JwtService;
use App\Modules\Core\Infrastructure\Support\Logger;
use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\Support\SessionHelper;
use PHPUnit\Framework\TestCase;

class SupportClassesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    public function test_auth_helper_class_exists(): void
    {
        $this->assertTrue(class_exists(AuthHelper::class));
    }

    public function test_jwt_service_class_exists(): void
    {
        $this->assertTrue(class_exists(JwtService::class));
    }

    public function test_logger_class_exists(): void
    {
        $this->assertTrue(class_exists(Logger::class));
    }

    public function test_mailer_class_exists(): void
    {
        $this->assertTrue(class_exists(Mailer::class));
    }

    public function test_session_helper_class_exists(): void
    {
        $this->assertTrue(class_exists(SessionHelper::class));
    }

    public function test_auth_helper_check_returns_false_when_not_logged_in(): void
    {
        $this->assertFalse(AuthHelper::check());
    }

    public function test_auth_helper_guest_returns_true_when_not_logged_in(): void
    {
        $this->assertTrue(AuthHelper::guest());
    }

    public function test_auth_helper_user_returns_null_when_not_logged_in(): void
    {
        $this->assertNull(AuthHelper::user());
    }

    public function test_auth_helper_csrf_token_returns_empty_string_when_not_set(): void
    {
        $this->assertEquals('', AuthHelper::csrfToken());
    }
}
