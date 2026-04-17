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

    public function test_enable_globally_sets_auto_detection_enabled(): void
    {
        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());

        AutoRelationConfig::enableGlobally();

        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function test_disable_globally_sets_auto_detection_disabled(): void
    {
        AutoRelationConfig::enableGlobally();
        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());

        AutoRelationConfig::disableGlobally();

        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function test_enable_for_specific_model(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::enableFor($testClass);

        $this->assertTrue(AutoRelationConfig::isEnabledFor($testClass));
    }

    public function test_enable_for_multiple_models(): void
    {
        $classes = ['Model1', 'Model2', 'Model3'];

        AutoRelationConfig::enableFor($classes);

        foreach ($classes as $class) {
            $this->assertTrue(AutoRelationConfig::isEnabledFor($class));
        }
    }

    public function test_disable_for_specific_model(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::enableFor($testClass);
        $this->assertTrue(AutoRelationConfig::isEnabledFor($testClass));

        AutoRelationConfig::disableFor($testClass);

        $this->assertFalse(AutoRelationConfig::isEnabledFor($testClass));
    }

    public function test_disable_for_multiple_models(): void
    {
        $classes = ['Model1', 'Model2'];

        AutoRelationConfig::enableFor($classes);
        AutoRelationConfig::disableFor($classes);

        foreach ($classes as $class) {
            $this->assertFalse(AutoRelationConfig::isEnabledFor($class));
        }
    }

    public function test_disabled_model_overrides_global_enable(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::enableGlobally();
        AutoRelationConfig::disableFor($testClass);

        $this->assertFalse(AutoRelationConfig::isEnabledFor($testClass));
        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function test_enabled_model_overrides_global_disable(): void
    {
        $testClass = 'TestModel';

        AutoRelationConfig::disableGlobally();
        AutoRelationConfig::enableFor($testClass);

        $this->assertTrue(AutoRelationConfig::isEnabledFor($testClass));
        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function test_set_max_auto_load_relations(): void
    {
        AutoRelationConfig::setMaxAutoLoadRelations(5);

        $this->assertSame(5, AutoRelationConfig::getMaxAutoLoadRelations());
    }

    public function test_reset_restores_defaults(): void
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

    public function test_configure_from_array(): void
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

    public function test_configure_with_enable_for(): void
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

    public function test_enable_lazy_loading_detection(): void
    {
        $this->assertFalse(AutoRelationConfig::isLazyLoadingDetectionEnabled());

        AutoRelationConfig::enableLazyLoadingDetection();

        $this->assertTrue(AutoRelationConfig::isLazyLoadingDetectionEnabled());
    }

    public function test_disable_lazy_loading_detection(): void
    {
        AutoRelationConfig::enableLazyLoadingDetection();
        $this->assertTrue(AutoRelationConfig::isLazyLoadingDetectionEnabled());

        AutoRelationConfig::disableLazyLoadingDetection();

        $this->assertFalse(AutoRelationConfig::isLazyLoadingDetectionEnabled());
    }
}
