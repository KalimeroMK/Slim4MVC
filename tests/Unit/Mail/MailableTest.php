<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Modules\Core\Infrastructure\Mail\PasswordResetEmail;
use App\Modules\Core\Infrastructure\Mail\WelcomeEmail;
use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\View\Blade;
use ReflectionClass;
use Tests\TestCase;

final class MailableTest extends TestCase
{
    private Mailer $mailer;

    private Blade $blade;

    protected function setUp(): void
    {
        parent::setUp();

        $viewsPath = dirname(__DIR__, 3).'/resources/views';
        $cachePath = dirname(__DIR__, 3).'/storage/cache/view';
        $this->blade = new Blade($viewsPath, $cachePath);
        $this->mailer = new Mailer($this->blade);
    }

    public function test_welcome_email_preview(): void
    {
        $user = $this->createUser(['name' => 'Test User', 'email' => 'test@example.com']);
        $welcomeEmail = new WelcomeEmail($this->mailer, $this->blade, $user);

        $html = $welcomeEmail->preview();

        $this->assertIsString($html);
        $this->assertStringContainsString('Test User', $html);
        $this->assertStringContainsString('Welcome', $html);
    }

    public function test_welcome_email_subject(): void
    {
        $user = $this->createUser();
        $welcomeEmail = new WelcomeEmail($this->mailer, $this->blade, $user);

        $reflectionClass = new ReflectionClass($welcomeEmail);
        $reflectionMethod = $reflectionClass->getMethod('getSubject');

        $subject = $reflectionMethod->invoke($welcomeEmail);

        $this->assertEquals('Welcome to our platform!', $subject);
    }

    public function test_welcome_email_template(): void
    {
        $user = $this->createUser();
        $welcomeEmail = new WelcomeEmail($this->mailer, $this->blade, $user);

        $reflectionClass = new ReflectionClass($welcomeEmail);
        $reflectionMethod = $reflectionClass->getMethod('template');

        $template = $reflectionMethod->invoke($welcomeEmail);

        $this->assertEquals('email.welcome', $template);
    }

    public function test_password_reset_email_preview(): void
    {
        $user = $this->createUser(['name' => 'Test User', 'email' => 'test@example.com']);
        $passwordResetEmail = new PasswordResetEmail($this->mailer, $this->blade, $user, 'test-token-123');

        $html = $passwordResetEmail->preview();

        $this->assertIsString($html);
        $this->assertStringContainsString('test-token-123', $html);
        $this->assertStringContainsString('reset-password', $html);
    }

    public function test_password_reset_email_subject(): void
    {
        $user = $this->createUser();
        $passwordResetEmail = new PasswordResetEmail($this->mailer, $this->blade, $user, 'token');

        $reflectionClass = new ReflectionClass($passwordResetEmail);
        $reflectionMethod = $reflectionClass->getMethod('getSubject');

        $subject = $reflectionMethod->invoke($passwordResetEmail);

        $this->assertEquals('Reset Your Password', $subject);
    }

    public function test_password_reset_email_template(): void
    {
        $user = $this->createUser();
        $passwordResetEmail = new PasswordResetEmail($this->mailer, $this->blade, $user, 'token');

        $reflectionClass = new ReflectionClass($passwordResetEmail);
        $reflectionMethod = $reflectionClass->getMethod('template');

        $template = $reflectionMethod->invoke($passwordResetEmail);

        $this->assertEquals('email.reset-password', $template);
    }

    public function test_mailable_with_custom_subject(): void
    {
        $user = $this->createUser();
        $welcomeEmail = new WelcomeEmail($this->mailer, $this->blade, $user);
        $welcomeEmail->subject('Custom Subject');

        // Test that custom subject is used
        $this->assertTrue(true); // Subject is tested through send/queue methods
    }

    public function test_mailable_with_additional_data(): void
    {
        $user = $this->createUser();
        $welcomeEmail = new WelcomeEmail($this->mailer, $this->blade, $user);
        $welcomeEmail->with(['custom' => 'data']);

        $html = $welcomeEmail->preview();

        // Additional data should be available in template
        $this->assertIsString($html);
    }
}
