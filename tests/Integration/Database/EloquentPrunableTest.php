<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Mockery as m;

/** @group SkipMSSQL */
class EloquentPrunableTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($container = new Container);

        $container->singleton(Dispatcher::class, function () {
            return m::mock(Dispatcher::class);
        });

        $container->alias(Dispatcher::class, 'events');
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        collect([
            'prunable_test_models',
            'prunable_soft_delete_test_models',
            'prunable_test_model_missing_prunable_methods',
            'prunable_with_custom_prune_method_test_models',
        ])->each(function ($table) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->softDeletes();
                $table->boolean('pruned')->default(false);
                $table->timestamps();
            });
        });
    }

    public function testPrunableMethodMustBeImplemented()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Please implement',
        );

        PrunableTestModelMissingPrunableMethod::create()->pruneAll();
    }

    public function testPrunesRecords()
    {
        app('events')
            ->shouldReceive('dispatch')
            ->times(2)
            ->with(m::type(ModelsPruned::class));

        collect(range(1, 5000))->map(function ($id) {
            return ['id' => $id];
        })->chunk(200)->each(function ($chunk) {
            PrunableTestModel::insert($chunk->all());
        });

        $count = (new PrunableTestModel)->pruneAll();

        $this->assertEquals(1500, $count);
        $this->assertEquals(3500, PrunableTestModel::count());
    }

    public function testPrunesSoftDeletedRecords()
    {
        app('events')
            ->shouldReceive('dispatch')
            ->times(3)
            ->with(m::type(ModelsPruned::class));

        collect(range(1, 5000))->map(function ($id) {
            return ['id' => $id, 'deleted_at' => now()];
        })->chunk(200)->each(function ($chunk) {
            PrunableSoftDeleteTestModel::insert($chunk->all());
        });

        $count = (new PrunableSoftDeleteTestModel)->pruneAll();

        $this->assertEquals(3000, $count);
        $this->assertEquals(0, PrunableSoftDeleteTestModel::count());
        $this->assertEquals(2000, PrunableSoftDeleteTestModel::withTrashed()->count());
    }

    public function testPruneWithCustomPruneMethod()
    {
        app('events')
            ->shouldReceive('dispatch')
            ->times(1)
            ->with(m::type(ModelsPruned::class));

        collect(range(1, 5000))->map(function ($id) {
            return ['id' => $id];
        })->chunk(200)->each(function ($chunk) {
            PrunableWithCustomPruneMethodTestModel::insert($chunk->all());
        });

        $count = (new PrunableWithCustomPruneMethodTestModel)->pruneAll();

        $this->assertEquals(1000, $count);
        $this->assertTrue((bool) PrunableWithCustomPruneMethodTestModel::first()->pruned);
        $this->assertFalse((bool) PrunableWithCustomPruneMethodTestModel::orderBy('id', 'desc')->first()->pruned);
        $this->assertEquals(5000, PrunableWithCustomPruneMethodTestModel::count());
    }
}

class PrunableTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1500);
    }
}

class PrunableSoftDeleteTestModel extends Model
{
    use Prunable, SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 3000);
    }
}

class PrunableWithCustomPruneMethodTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1000);
    }

    public function prune()
    {
        $this->forceFill([
            'pruned' => true,
        ])->save();
    }
}

class PrunableTestModelMissingPrunableMethod extends Model
{
    use Prunable;
}
