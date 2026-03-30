<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use App\Modules\Core\Infrastructure\View\Blade;
use PHPUnit\Framework\TestCase;

final class BladeViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_ENV'] = 'testing';
        $_SESSION['csrf_token'] = 'test-token';
    }

    public function test_blade_class_exists(): void
    {
        $this->assertTrue(class_exists(Blade::class));
    }

    public function test_blade_can_be_instantiated(): void
    {
        $blade = new Blade('resources/views', 'storage/cache/view');
        $this->assertInstanceOf(Blade::class, $blade);
    }

    public function test_blade_has_make_method(): void
    {
        $this->assertTrue(method_exists(Blade::class, 'make'));
    }

    public function test_blade_has_share_method(): void
    {
        $this->assertTrue(method_exists(Blade::class, 'share'));
    }

    public function test_blade_has_exists_method(): void
    {
        $this->assertTrue(method_exists(Blade::class, 'exists'));
    }
}
