<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use LogicException;

class EloquentPrunableTest extends DatabaseTestCase
{
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
                $table->string('name')->nullable();
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
        Event::fake();

        PrunableTestModel::insert(collect(range(1, 50))->map(function ($id) {
            return ['name' => 'foo'];
        })->all());

        $count = (new PrunableTestModel)->pruneAll(10);

        $this->assertEquals(15, $count);
        $this->assertEquals(35, PrunableTestModel::count());

        Event::assertDispatched(ModelsPruned::class, 2);
    }

    public function testPrunesSoftDeletedRecords()
    {
        Event::fake();

        PrunableSoftDeleteTestModel::insert(collect(range(1, 50))->map(function ($id) {
            return ['deleted_at' => now()];
        })->all());

        $count = (new PrunableSoftDeleteTestModel)->pruneAll(10);

        $this->assertEquals(30, $count);
        $this->assertEquals(0, PrunableSoftDeleteTestModel::count());
        $this->assertEquals(20, PrunableSoftDeleteTestModel::withTrashed()->count());

        Event::assertDispatched(ModelsPruned::class, 3);
    }

    public function testPruneWithCustomPruneMethod()
    {
        Event::fake();

        PrunableWithCustomPruneMethodTestModel::insert(collect(range(1, 50))->map(function ($id) {
            return ['name' => 'foo'];
        })->all());

        $count = (new PrunableWithCustomPruneMethodTestModel)->pruneAll(10);

        $this->assertEquals(10, $count);
        $this->assertTrue((bool) PrunableWithCustomPruneMethodTestModel::first()->pruned);
        $this->assertFalse((bool) PrunableWithCustomPruneMethodTestModel::orderBy('id', 'desc')->first()->pruned);
        $this->assertEquals(50, PrunableWithCustomPruneMethodTestModel::count());

        Event::assertDispatched(ModelsPruned::class, 1);
    }
}

class PrunableTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 15);
    }
}

class PrunableSoftDeleteTestModel extends Model
{
    use Prunable, SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 30);
    }
}

class PrunableWithCustomPruneMethodTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 10);
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
