<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Database\Eloquent;

use App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

/**
 * Test models for helper functions
 */
class HelperTestModel extends Model
{
    /** @var string */
    protected $table = 'helper_test_models';

    public function children(): HasMany
    {
        return $this->hasMany(HelperTestChild::class, 'parent_id');
    }
}

class HelperTestChild extends Model
{
    /** @var string */
    protected $table = 'helper_test_children';
}

/**
 * @covers \preload
 * @covers \preload_missing
 * @covers \enable_auto_eager_loading
 * @covers \disable_auto_eager_loading
 * @covers \detect_lazy_loading
 * @covers \clear_relation_cache
 */
final class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset config
        AutoRelationConfig::reset();

        // Disable mass assignment protection for tests
        Model::unguard();

        // Setup in-memory SQLite database
        $manager = new Capsule;
        $manager->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $manager->setAsGlobal();
        $manager->bootEloquent();

        // Create test tables
        $manager->schema()->create('helper_test_models', function ($table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $manager->schema()->create('helper_test_children', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('parent_id');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Model::reguard();
        AutoRelationConfig::reset();
        parent::tearDown();
    }

    public function test_preload_loads_relations_on_model(): void
    {
        $parent = HelperTestModel::create(['name' => 'Parent']);
        HelperTestChild::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $fresh = HelperTestModel::find($parent->id);
        $this->assertFalse($fresh->relationLoaded('children'));

        $result = preload($fresh, 'children');

        $this->assertSame($fresh, $result);
        $this->assertTrue($fresh->relationLoaded('children'));
    }

    public function test_preload_loads_relations_on_collection(): void
    {
        $parent1 = HelperTestModel::create(['name' => 'Parent 1']);
        $parent2 = HelperTestModel::create(['name' => 'Parent 2']);
        HelperTestChild::create(['parent_id' => $parent1->id, 'name' => 'Child 1']);
        HelperTestChild::create(['parent_id' => $parent2->id, 'name' => 'Child 2']);

        $parents = HelperTestModel::all();

        $result = preload($parents, 'children');

        $this->assertSame($parents, $result);
        foreach ($parents as $parent) {
            $this->assertTrue($parent->relationLoaded('children'));
        }
    }

    public function test_preload_with_array_relations(): void
    {
        $parent = HelperTestModel::create(['name' => 'Parent']);
        HelperTestChild::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $fresh = HelperTestModel::find($parent->id);
        preload($fresh, ['children']);

        $this->assertTrue($fresh->relationLoaded('children'));
    }

    public function test_preload_missing_loads_only_missing(): void
    {
        $parent = HelperTestModel::create(['name' => 'Parent']);
        HelperTestChild::create(['parent_id' => $parent->id, 'name' => 'Child']);

        // Preload with one relation already loaded
        $fresh = HelperTestModel::with('children')->find($parent->id);
        $this->assertTrue($fresh->relationLoaded('children'));

        $parents = \Illuminate\Database\Eloquent\Collection::make([$fresh]);
        $result = preload_missing($parents, 'children');

        $this->assertSame($parents, $result);
        $this->assertTrue($fresh->relationLoaded('children'));
    }

    public function test_enable_auto_eager_loading(): void
    {
        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());

        enable_auto_eager_loading();

        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function test_disable_auto_eager_loading(): void
    {
        enable_auto_eager_loading();
        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());

        disable_auto_eager_loading();

        $this->assertFalse(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function test_detect_lazy_loading(): void
    {
        $this->assertFalse(AutoRelationConfig::isLazyLoadingDetectionEnabled());

        detect_lazy_loading();

        $this->assertTrue(AutoRelationConfig::isLazyLoadingDetectionEnabled());
    }

    public function test_clear_relation_cache(): void
    {
        // This should not throw an exception
        clear_relation_cache();
        clear_relation_cache(HelperTestModel::class);

        // If we get here, the test passed
        $this->assertTrue(true);
    }
}
