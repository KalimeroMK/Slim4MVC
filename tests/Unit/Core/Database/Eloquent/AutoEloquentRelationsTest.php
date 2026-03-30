<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Database\Eloquent;

use App\Modules\Core\Infrastructure\Database\Eloquent\AutoEloquentRelations;
use App\Modules\Core\Infrastructure\Database\Eloquent\AutoRelationConfig;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

/**
 * Test models for AutoEagerLoading
 */
class TestAutoLoadModel extends Model
{
    use AutoEloquentRelations;

    /** @var string */
    protected $table = 'test_models';
    
    protected array $autoWith = ['children'];

    public function children(): HasMany
    {
        return $this->hasMany(TestChildModel::class, 'parent_id');
    }

    public function unusedRelation(): HasMany
    {
        return $this->hasMany(TestChildModel::class, 'parent_id');
    }
}

class TestChildModel extends Model
{
    /** @var string */
    protected $table = 'test_children';
}

class TestNoAutoModel extends Model
{
    use AutoEloquentRelations;

    /** @var string */
    protected $table = 'test_no_auto';
    
    protected array $autoWith = [];
}

class TestExcludeModel extends Model
{
    use AutoEloquentRelations;

    /** @var string */
    protected $table = 'test_exclude';
    
    protected array $excludeAutoWith = ['secretRelation'];

    public function publicRelation(): HasMany
    {
        return $this->hasMany(TestChildModel::class, 'parent_id');
    }

    public function secretRelation(): HasMany
    {
        return $this->hasMany(TestChildModel::class, 'parent_id');
    }
}

/**
 * @covers \App\Modules\Core\Infrastructure\Database\Eloquent\AutoEloquentRelations
 */
final class AutoEloquentRelationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset config before each test
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
        $manager->schema()->create('test_models', function ($table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $manager->schema()->create('test_no_auto', function ($table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $manager->schema()->create('test_children', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('parent_id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // Clear relation cache
        TestAutoLoadModel::clearRelationCache();
        TestExcludeModel::clearRelationCache();
    }

    protected function tearDown(): void
    {
        // Re-enable mass assignment protection
        Model::reguard();

        // Reset config
        AutoRelationConfig::reset();
        
        // Clear relation cache
        TestAutoLoadModel::clearRelationCache();
        TestExcludeModel::clearRelationCache();

        parent::tearDown();
    }

    public function testAutoWithLoadsRelationsAutomatically(): void
    {
        // Create parent and children
        $parent = TestAutoLoadModel::create(['name' => 'Parent']);
        TestChildModel::create(['parent_id' => $parent->id, 'name' => 'Child 1']);
        TestChildModel::create(['parent_id' => $parent->id, 'name' => 'Child 2']);

        // Clear cache to simulate fresh request
        TestAutoLoadModel::clearRelationCache();

        // Find the model - children should be auto-loaded
        $found = TestAutoLoadModel::find($parent->id);

        // Verify relation is loaded
        $this->assertTrue($found->relationLoaded('children'));
        $this->assertCount(2, $found->children);
    }

    public function testQueryWithoutAutoWithSkipsAutoLoading(): void
    {
        // Create parent and children
        $parent = TestAutoLoadModel::create(['name' => 'Parent']);
        TestChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        // Use query without auto-with
        $found = TestAutoLoadModel::queryWithoutAutoWith()->find($parent->id);

        // Verify relation is NOT loaded
        $this->assertFalse($found->relationLoaded('children'));
    }

    public function testEmptyAutoWithDisablesAutoLoading(): void
    {
        // Create model
        $model = TestNoAutoModel::create(['name' => 'Test']);

        // Find the model - no relations should be auto-loaded
        $found = TestNoAutoModel::find($model->id);

        // No relations should be loaded
        $this->assertEmpty($found->getRelations());
    }

    public function testDetectRelationsReturnsRelationMethods(): void
    {
        $relations = TestAutoLoadModel::detectRelations();

        // Should detect both relation methods
        $this->assertContains('children', $relations);
        $this->assertContains('unusedRelation', $relations);
    }

    public function testDetectRelationsExcludesSpecifiedRelations(): void
    {
        $relations = TestExcludeModel::detectRelations();

        // Should detect publicRelation
        $this->assertContains('publicRelation', $relations);

        // Should NOT detect secretRelation (it's in excludeAutoWith)
        $this->assertNotContains('secretRelation', $relations);
    }

    public function testRelationCacheIsUsed(): void
    {
        // First call should cache
        $relations1 = TestAutoLoadModel::detectRelations();

        // Second call should use cache
        $relations2 = TestAutoLoadModel::detectRelations();

        // Should be identical
        $this->assertSame($relations1, $relations2);
    }

    public function testClearRelationCacheClearsSpecificModel(): void
    {
        // Populate cache
        TestAutoLoadModel::detectRelations();
        TestExcludeModel::detectRelations();

        // Clear only TestAutoLoadModel cache
        TestAutoLoadModel::clearRelationCache(TestAutoLoadModel::class);

        // Both should still work (just re-detected)
        $relations = TestAutoLoadModel::detectRelations();
        $this->assertNotEmpty($relations);
    }

    public function testClearRelationCacheClearsAllModels(): void
    {
        // Populate cache
        TestAutoLoadModel::detectRelations();

        // Clear all cache
        TestAutoLoadModel::clearRelationCache();

        // Should still work (re-detected)
        $relations = TestAutoLoadModel::detectRelations();
        $this->assertNotEmpty($relations);
    }

    public function testAutoDetectionGlobalEnable(): void
    {
        // Enable global auto-detection
        AutoRelationConfig::enableGlobally();

        // Model without autoWith should still auto-load when detection is enabled
        // (but since TestNoAutoModel has empty autoWith, it won't load anything)
        $this->assertTrue(AutoRelationConfig::isAutoDetectionEnabled());
    }

    public function testGetAutoLoadableRelationsReturnsExplicitRelations(): void
    {
        $relations = TestAutoLoadModel::getAutoLoadableRelations();

        $this->assertSame(['children'], $relations);
    }

    public function testGetAutoLoadableRelationsReturnsEmptyWhenNoAutoWith(): void
    {
        $relations = TestNoAutoModel::getAutoLoadableRelations();

        $this->assertEmpty($relations);
    }
}
