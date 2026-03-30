<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Database\Eloquent;

use App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig
 */
final class AutoRelationConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AutoRelationConfig::reset();
    }

    protected function tearDown(): void
    {
        AutoRelationConfig::reset();
        parent::tearDown();
    }

    public function testEnableGloballySetsAutoDetectionEnabled(): void
    {
        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());

        AutoRelationConfig::enableGlobally();

        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function testDisableGloballySetsAutoDetectionDisabled(): void
    {
        AutoRelationConfig::enableGlobally();
        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());

        AutoRelationConfig::disableGlobally();

        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function testEnableForSpecificModel(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::enableFor($testClass);

        $this->assertTrue(AutoRelationConfig::isEnabledFor($testClass));
    }

    public function testEnableForMultipleModels(): void
    {
        $classes = ['Model1', 'Model2', 'Model3'];

        AutoRelationConfig::enableFor($classes);

        foreach ($classes as $class) {
            $this->assertTrue(AutoRelationConfig::isEnabledFor($class));
        }
    }

    public function testDisableForSpecificModel(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::enableFor($testClass);
        $this->assertTrue(AutoRelationConfig::isEnabledFor($testClass));

        AutoRelationConfig::disableFor($testClass);

        $this->assertFalse(AutoRelationConfig::isEnabledFor($testClass));
    }

    public function testDisableForMultipleModels(): void
    {
        $classes = ['Model1', 'Model2'];

        AutoRelationConfig::enableFor($classes);
        AutoRelationConfig::disableFor($classes);

        foreach ($classes as $class) {
            $this->assertFalse(AutoRelationConfig::isEnabledFor($class));
        }
    }

    public function testDisabledModelOverridesGlobalEnable(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::enableGlobally();
        AutoRelationConfig::disableFor($testClass);

        $this->assertFalse(AutoRelationConfig::isEnabledFor($testClass));
        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function testEnabledModelOverridesGlobalDisable(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::disableGlobally();
        AutoRelationConfig::enableFor($testClass);

        $this->assertTrue(AutoRelationConfig::isEnabledFor($testClass));
        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function testSetMaxAutoLoadRelations(): void
    {
        AutoRelationConfig::setMaxAutoLoadRelations(5);

        $this->assertSame(5, AutoRelationConfig::getMaxAutoLoadRelations());
    }

    public function testResetRestoresDefaults(): void
    {
        // Change some settings
        AutoRelationConfig::enableGlobally();
        AutoRelationConfig::setMaxAutoLoadRelations(20);
        AutoRelationConfig::enableFor('TestModel');

        // Reset
        AutoRelationConfig::reset();

        // Verify defaults restored
        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());
        $this->assertSame(10, AutoRelationConfig::getMaxAutoLoadRelations());
        $this->assertFalse(AutoRelationConfig::isEnabledFor('TestModel'));
    }

    public function testConfigureFromArray(): void
    {
        $config = [
            'enabled' => true,
            'mode' => 'detection',
            'max_relations' => 15,
            'enable_for' => ['Model1', 'Model2'],
            'lazy_loading_detection' => false,
        ];

        AutoRelationConfig::configure($config);

        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
        $this->assertSame(15, AutoRelationConfig::getMaxAutoLoadRelations());
        $this->assertTrue(AutoRelationConfig::isEnabledFor('Model1'));
        $this->assertTrue(AutoRelationConfig::isEnabledFor('Model2'));
    }

    public function testConfigureWithEnableFor(): void
    {
        $config = [
            'enabled' => true,
            'enable_for' => ['Model1'],
        ];

        AutoRelationConfig::configure($config);

        // Global should be enabled and specific models should be enabled
        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
        $this->assertTrue(AutoRelationConfig::isEnabledFor('Model1'));
    }

    public function testEnableLazyLoadingDetection(): void
    {
        $this->assertFalse(AutoRelationConfig::isLazyLoadingDetectionEnabled());

        AutoRelationConfig::enableLazyLoadingDetection();

        $this->assertTrue(AutoRelationConfig::isLazyLoadingDetectionEnabled());
    }

    public function testDisableLazyLoadingDetection(): void
    {
        AutoRelationConfig::enableLazyLoadingDetection();
        $this->assertTrue(AutoRelationConfig::isLazyLoadingDetectionEnabled());

        AutoRelationConfig::disableLazyLoadingDetection();

        $this->assertFalse(AutoRelationConfig::isLazyLoadingDetectionEnabled());
    }
}
