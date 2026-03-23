<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Modules\Core\Infrastructure\Mail\WelcomeEmail;
use App\Modules\Core\Infrastructure\Mail\PasswordResetEmail;
use PHPUnit\Framework\TestCase;

class WelcomeEmailTest extends TestCase
{
    public function test_welcome_email_class_exists(): void
    {
        $this->assertTrue(class_exists(WelcomeEmail::class));
    }

    public function test_password_reset_email_class_exists(): void
    {
        $this->assertTrue(class_exists(PasswordResetEmail::class));
    }

    public function test_welcome_email_has_template_method(): void
    {
        $this->assertTrue(method_exists(WelcomeEmail::class, 'template'));
    }

    public function test_password_reset_email_has_template_method(): void
    {
        $this->assertTrue(method_exists(PasswordResetEmail::class, 'template'));
    }
}
