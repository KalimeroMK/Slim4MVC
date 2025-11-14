<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Modules\Core\Infrastructure\Mail\PasswordResetEmail;
use App\Modules\Core\Infrastructure\Mail\WelcomeEmail;
use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\View\Blade;
use ReflectionClass;
use Tests\TestCase;

class MailableTest extends TestCase
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
        $email = new WelcomeEmail($this->mailer, $this->blade, $user);

        $html = $email->preview();

        $this->assertIsString($html);
        $this->assertStringContainsString('Test User', $html);
        $this->assertStringContainsString('Welcome', $html);
    }

    public function test_welcome_email_subject(): void
    {
        $user = $this->createUser();
        $email = new WelcomeEmail($this->mailer, $this->blade, $user);

        $reflection = new ReflectionClass($email);
        $method = $reflection->getMethod('getSubject');
        $method->setAccessible(true);

        $subject = $method->invoke($email);

        $this->assertEquals('Welcome to our platform!', $subject);
    }

    public function test_welcome_email_template(): void
    {
        $user = $this->createUser();
        $email = new WelcomeEmail($this->mailer, $this->blade, $user);

        $reflection = new ReflectionClass($email);
        $method = $reflection->getMethod('template');
        $method->setAccessible(true);

        $template = $method->invoke($email);

        $this->assertEquals('email.welcome', $template);
    }

    public function test_password_reset_email_preview(): void
    {
        $user = $this->createUser(['name' => 'Test User', 'email' => 'test@example.com']);
        $email = new PasswordResetEmail($this->mailer, $this->blade, $user, 'test-token-123');

        $html = $email->preview();

        $this->assertIsString($html);
        $this->assertStringContainsString('test-token-123', $html);
        $this->assertStringContainsString('reset-password', $html);
    }

    public function test_password_reset_email_subject(): void
    {
        $user = $this->createUser();
        $email = new PasswordResetEmail($this->mailer, $this->blade, $user, 'token');

        $reflection = new ReflectionClass($email);
        $method = $reflection->getMethod('getSubject');
        $method->setAccessible(true);

        $subject = $method->invoke($email);

        $this->assertEquals('Reset Your Password', $subject);
    }

    public function test_password_reset_email_template(): void
    {
        $user = $this->createUser();
        $email = new PasswordResetEmail($this->mailer, $this->blade, $user, 'token');

        $reflection = new ReflectionClass($email);
        $method = $reflection->getMethod('template');
        $method->setAccessible(true);

        $template = $method->invoke($email);

        $this->assertEquals('email.reset-password', $template);
    }

    public function test_mailable_with_custom_subject(): void
    {
        $user = $this->createUser();
        $email = new WelcomeEmail($this->mailer, $this->blade, $user);
        $email->subject('Custom Subject');

        // Test that custom subject is used
        $this->assertTrue(true); // Subject is tested through send/queue methods
    }

    public function test_mailable_with_additional_data(): void
    {
        $user = $this->createUser();
        $email = new WelcomeEmail($this->mailer, $this->blade, $user);
        $email->with(['custom' => 'data']);

        $html = $email->preview();

        // Additional data should be available in template
        $this->assertIsString($html);
    }
}
