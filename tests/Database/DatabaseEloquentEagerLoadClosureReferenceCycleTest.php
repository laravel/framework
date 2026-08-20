<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeakReference;

class DatabaseEloquentEagerLoadClosureReferenceCycleTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();

        $schema = $db->getConnection()->getSchemaBuilder();

        $schema->create('parents', function ($table) {
            $table->increments('id');
        });

        $schema->create('children', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id');
            $table->boolean('active')->default(true);
        });

        gc_disable();
    }

    protected function tearDown(): void
    {
        gc_enable();

        Model::unsetConnectionResolver();
    }

    #[DataProvider('eagerLoadProvider')]
    public function testBuilderWithEagerLoadIsReleasedWithoutCycleCollection(callable $makeBuilder)
    {
        $builder = $makeBuilder();

        $this->assertArrayHasKey('children', $builder->getEagerLoads());

        $reference = WeakReference::create($builder);

        unset($builder);

        $this->assertNull($reference->get());
    }

    public function testModelWithLoadedRelationIsReleasedWithoutCycleCollection()
    {
        $parent = EloquentEagerLoadCycleParentStub::create();

        EloquentEagerLoadCycleChildStub::create(['parent_id' => $parent->id]);

        $parent->load('children');

        $this->assertCount(1, $parent->children);

        $reference = WeakReference::create($parent);

        unset($parent);

        $this->assertNull($reference->get());
    }

    public static function eagerLoadProvider()
    {
        return [
            'with string relation' => [
                fn () => EloquentEagerLoadCycleParentStub::query()->with('children'),
            ],
            'with constrained relation' => [
                fn () => EloquentEagerLoadCycleParentStub::query()->with([
                    'children' => fn ($query) => $query->where('active', true),
                ]),
            ],
            'with column selection' => [
                fn () => EloquentEagerLoadCycleParentStub::query()->with('children:id,parent_id'),
            ],
            'withWhereHas' => [
                fn () => EloquentEagerLoadCycleParentStub::query()->withWhereHas(
                    'children',
                    fn ($query) => $query->where('active', true),
                ),
            ],
            'withWhereRelation' => [
                fn () => EloquentEagerLoadCycleParentStub::query()->withWhereRelation('children', 'active', true),
            ],
        ];
    }
}

class EloquentEagerLoadCycleParentStub extends Model
{
    protected $table = 'parents';

    public $timestamps = false;

    public function children()
    {
        return $this->hasMany(EloquentEagerLoadCycleChildStub::class, 'parent_id');
    }
}

class EloquentEagerLoadCycleChildStub extends Model
{
    protected $table = 'children';

    protected $guarded = [];

    public $timestamps = false;
}
