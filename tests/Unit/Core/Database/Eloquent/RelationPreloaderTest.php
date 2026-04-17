<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Database\Eloquent;

use App\Modules\Core\Infrastructure\Database\Eloquent\RelationPreloader;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

/**
 * Test models for RelationPreloader
 */
class PreloadParentModel extends Model
{
    /** @var string */
    protected $table = 'preload_parents';

    public function children(): HasMany
    {
        return $this->hasMany(PreloadChildModel::class, 'parent_id');
    }

    public function otherChildren(): HasMany
    {
        return $this->hasMany(PreloadChildModel::class, 'parent_id');
    }
}

class PreloadChildModel extends Model
{
    /** @var string */
    protected $table = 'preload_children';
}

/**
 * @covers \App\Modules\Core\Infrastructure\Database\Eloquent\RelationPreloader
 */
final class RelationPreloaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        $manager->schema()->create('preload_parents', function ($table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $manager->schema()->create('preload_children', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('parent_id');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    public function test_load_loads_relations_on_single_model(): void
    {
        // Create parent and children
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child 1']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child 2']);

        // Fresh instance without relations
        $fresh = PreloadParentModel::find($parent->id);
        $this->assertFalse($fresh->relationLoaded('children'));

        // Load relations
        $model = RelationPreloader::load($fresh, 'children');

        // Should be the same instance
        $this->assertSame($fresh, $model);

        // Relation should now be loaded
        $this->assertTrue($fresh->relationLoaded('children'));
        $this->assertCount(2, $fresh->children);
    }

    public function test_load_with_array_relations(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $fresh = PreloadParentModel::find($parent->id);

        // Load multiple relations
        RelationPreloader::load($fresh, ['children', 'otherChildren']);

        $this->assertTrue($fresh->relationLoaded('children'));
        $this->assertTrue($fresh->relationLoaded('otherChildren'));
    }

    public function test_load_skips_already_loaded_relations(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $fresh = PreloadParentModel::with('children')->find($parent->id);
        $this->assertTrue($fresh->relationLoaded('children'));

        // Should not cause any new queries
        $preloadParentModel = RelationPreloader::load($fresh, 'children');

        $this->assertSame($fresh, $preloadParentModel);
        $this->assertTrue($fresh->relationLoaded('children'));
    }

    public function test_load_many_loads_relations_on_collection(): void
    {
        // Create parents and children
        $parent1 = PreloadParentModel::create(['name' => 'Parent 1']);
        $parent2 = PreloadParentModel::create(['name' => 'Parent 2']);

        PreloadChildModel::create(['parent_id' => $parent1->id, 'name' => 'Child 1']);
        PreloadChildModel::create(['parent_id' => $parent2->id, 'name' => 'Child 2']);

        // Get collection without relations
        $parents = PreloadParentModel::all();
        $this->assertCount(2, $parents);

        foreach ($parents as $parent) {
            $this->assertFalse($parent->relationLoaded('children'));
        }

        // Load relations on collection
        $result = RelationPreloader::loadMany($parents, 'children');

        // Should be the same collection
        $this->assertSame($parents, $result);

        // All should have relations loaded
        foreach ($parents as $parent) {
            $this->assertTrue($parent->relationLoaded('children'));
        }
    }

    public function test_load_many_skips_already_loaded(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $parents = PreloadParentModel::with('children')->get();

        // Should not cause any new queries
        RelationPreloader::loadMany($parents, 'children');

        $this->assertTrue($parents->first()->relationLoaded('children'));
    }

    public function test_load_missing_only_loads_missing_relations(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        // Load one relation first
        $fresh = PreloadParentModel::with('children')->find($parent->id);
        $this->assertTrue($fresh->relationLoaded('children'));
        $this->assertFalse($fresh->relationLoaded('otherChildren'));

        // Load missing should only load otherChildren
        $parents = new Collection([$fresh]);
        RelationPreloader::loadMissing($parents, ['children', 'otherChildren']);

        $this->assertTrue($fresh->relationLoaded('children'));
        $this->assertTrue($fresh->relationLoaded('otherChildren'));
    }

    public function test_has_loaded_returns_true_when_all_loaded(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $parents = PreloadParentModel::with(['children', 'otherChildren'])->get();

        $this->assertTrue(RelationPreloader::hasLoaded($parents, 'children'));
        $this->assertTrue(RelationPreloader::hasLoaded($parents, ['children', 'otherChildren']));
    }

    public function test_has_loaded_returns_false_when_not_all_loaded(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $parents = PreloadParentModel::with('children')->get();

        $this->assertFalse(RelationPreloader::hasLoaded($parents, ['children', 'otherChildren']));
    }

    public function test_has_loaded_returns_true_for_empty_collection(): void
    {
        $empty = new Collection();

        $this->assertTrue(RelationPreloader::hasLoaded($empty, 'children'));
    }

    public function test_get_missing_relations_returns_empty_when_all_loaded(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $parents = PreloadParentModel::with('children')->get();

        $missing = RelationPreloader::getMissingRelations($parents, 'children');

        $this->assertEmpty($missing);
    }

    public function test_get_missing_relations_returns_missing_relation_names(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $parents = PreloadParentModel::with('children')->get();

        $missing = RelationPreloader::getMissingRelations($parents, ['children', 'otherChildren']);

        $this->assertSame(['otherChildren'], $missing);
    }

    public function test_with_adds_relations_to_query(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);
        PreloadChildModel::create(['parent_id' => $parent->id, 'name' => 'Child']);

        $query = PreloadParentModel::query();
        $result = RelationPreloader::with($query, 'children')->first();
        $this->assertInstanceOf(PreloadParentModel::class, $result);

        $this->assertTrue($result->relationLoaded('children'));
    }

    public function test_load_many_returns_empty_collection_unchanged(): void
    {
        $empty = new Collection();

        $result = RelationPreloader::loadMany($empty, 'children');

        $this->assertSame($empty, $result);
    }

    public function test_load_returns_model_when_no_relations_specified(): void
    {
        $parent = PreloadParentModel::create(['name' => 'Parent']);

        $model = RelationPreloader::load($parent, []);

        $this->assertSame($parent, $model);
    }
}
