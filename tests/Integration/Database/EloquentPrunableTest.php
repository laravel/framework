<?php

namespace Illuminate\Tests\Integration\Database;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Events\ModelsSoftPruned;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use LogicException;

class EloquentPrunableTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        collect([
            'prunable_test_models',
            'prunable_soft_delete_test_models',
            'prunable_test_model_missing_prunable_methods',
            'prunable_with_custom_prune_method_test_models',
            'prunable_with_exceptions',
            'soft_prunable_test_models',
            'soft_prunable_without_soft_deletes_test_models',
            'soft_prunable_with_hook_test_models',
            'prunable_with_soft_hook_test_models',
            'prunable_both_window_test_models',
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

        collect(range(1, 5000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            PrunableTestModel::insert($chunk->all());
        });

        $count = (new PrunableTestModel)->pruneAll();

        $this->assertEquals(1500, $count);
        $this->assertEquals(3500, PrunableTestModel::count());

        Event::assertDispatched(ModelsPruned::class, 2);
    }

    public function testPrunesSoftDeletedRecords()
    {
        Event::fake();

        collect(range(1, 5000))->map(function ($id) {
            return ['deleted_at' => Carbon::now()];
        })->chunk(200)->each(function ($chunk) {
            PrunableSoftDeleteTestModel::insert($chunk->all());
        });

        $count = (new PrunableSoftDeleteTestModel)->pruneAll();

        $this->assertEquals(3000, $count);
        $this->assertEquals(0, PrunableSoftDeleteTestModel::count());
        $this->assertEquals(2000, PrunableSoftDeleteTestModel::withTrashed()->count());

        Event::assertDispatched(ModelsPruned::class, 3);
    }

    public function testPruneWithCustomPruneMethod()
    {
        Event::fake();

        collect(range(1, 5000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            PrunableWithCustomPruneMethodTestModel::insert($chunk->all());
        });

        $count = (new PrunableWithCustomPruneMethodTestModel)->pruneAll();

        $this->assertEquals(1000, $count);
        $this->assertTrue((bool) PrunableWithCustomPruneMethodTestModel::first()->pruned);
        $this->assertFalse((bool) PrunableWithCustomPruneMethodTestModel::orderBy('id', 'desc')->first()->pruned);
        $this->assertEquals(5000, PrunableWithCustomPruneMethodTestModel::count());

        Event::assertDispatched(ModelsPruned::class, 1);
    }

    public function testSoftPrunesRecords()
    {
        Event::fake();

        collect(range(1, 5000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            SoftPrunableTestModel::insert($chunk->all());
        });

        $count = (new SoftPrunableTestModel)->pruneAll();

        // The 3000 matching records are soft deleted (across 3 chunks of 1000),
        // not hard deleted: nothing leaves the table, and they are now trashed.
        $this->assertEquals(3000, $count);
        $this->assertEquals(2000, SoftPrunableTestModel::count());
        $this->assertEquals(3000, SoftPrunableTestModel::onlyTrashed()->count());
        $this->assertEquals(5000, SoftPrunableTestModel::withTrashed()->count());

        Event::assertDispatched(ModelsSoftPruned::class, 3);
        Event::assertNotDispatched(ModelsPruned::class);
    }

    public function testSoftPruningIsIdempotentAcrossRuns()
    {
        collect(range(1, 5000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            SoftPrunableTestModel::insert($chunk->all());
        });

        $this->assertEquals(3000, (new SoftPrunableTestModel)->pruneAll());

        // A second run finds nothing: already-trashed records are excluded by the
        // default soft-deleting scope, so they are never re-processed.
        $this->assertEquals(0, (new SoftPrunableTestModel)->pruneAll());
        $this->assertEquals(2000, SoftPrunableTestModel::count());
        $this->assertEquals(5000, SoftPrunableTestModel::withTrashed()->count());
    }

    public function testSoftPruningThrowsWithoutSoftDeletes()
    {
        collect(range(1, 100))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(50)->each(function ($chunk) {
            SoftPrunableWithoutSoftDeletesTestModel::insert($chunk->all());
        });

        try {
            (new SoftPrunableWithoutSoftDeletesTestModel)->pruneAll();
            $this->fail('Expected a LogicException to be thrown.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('does not use the SoftDeletes trait', $e->getMessage());
        }

        // No records were touched before the guard threw.
        $this->assertEquals(100, SoftPrunableWithoutSoftDeletesTestModel::count());
    }

    public function testSoftPruningHookFiresOnlyOnSoftPrune()
    {
        SoftPrunableWithHookTestModel::$pruned = 0;
        PrunableWithSoftHookTestModel::$pruned = 0;

        collect(range(1, 1000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            SoftPrunableWithHookTestModel::insert($chunk->all());
            PrunableWithSoftHookTestModel::insert($chunk->all());
        });

        (new SoftPrunableWithHookTestModel)->pruneAll();
        (new PrunableWithSoftHookTestModel)->pruneAll();

        // The softPruning() hook fires for every soft-pruned record...
        $this->assertEquals(1000, SoftPrunableWithHookTestModel::$pruned);
        $this->assertEquals(1000, SoftPrunableWithHookTestModel::onlyTrashed()->count());

        // ...but never for a model that hard-prunes (force deletes).
        $this->assertEquals(0, PrunableWithSoftHookTestModel::$pruned);
        $this->assertEquals(0, PrunableWithSoftHookTestModel::withTrashed()->count());
    }

    public function testPrunesBothWindowsInOnePass()
    {
        Event::fake();

        collect(range(1, 5000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            PrunableBothWindowTestModel::insert($chunk->all());
        });

        $count = (new PrunableBothWindowTestModel)->pruneAll();

        // Hard window (id <= 1000) is force deleted, soft window (1000 < id <= 3000) is
        // soft deleted. The two windows are disjoint.
        $this->assertEquals(3000, $count);
        $this->assertEquals(4000, PrunableBothWindowTestModel::withTrashed()->count());
        $this->assertEquals(2000, PrunableBothWindowTestModel::count());
        $this->assertEquals(2000, PrunableBothWindowTestModel::onlyTrashed()->count());

        // Each window reports its own cumulative count through its own event.
        Event::assertDispatched(ModelsPruned::class, 1);
        Event::assertDispatched(fn (ModelsPruned $event) => $event->count === 1000);
        Event::assertDispatched(ModelsSoftPruned::class, 2);
        Event::assertDispatched(fn (ModelsSoftPruned $event) => $event->count === 2000);
    }

    public function testPruneWithExceptionAtOneOfModels()
    {
        Event::fake();
        Exceptions::fake();

        collect(range(1, 5000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            PrunableWithException::insert($chunk->all());
        });

        $count = (new PrunableWithException)->pruneAll();

        $this->assertEquals(999, $count);

        Event::assertDispatched(ModelsPruned::class, 1);
        Event::assertDispatched(fn (ModelsPruned $event) => $event->count === 999);
        Exceptions::assertReportedCount(1);
        Exceptions::assertReported(fn (Exception $exception) => $exception->getMessage() === 'foo bar');
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

class PrunableWithException extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1000);
    }

    public function prune()
    {
        if ($this->id === 500) {
            throw new Exception('foo bar');
        }
    }
}

class PrunableTestModelMissingPrunableMethod extends Model
{
    use Prunable;
}

class SoftPrunableTestModel extends Model
{
    use Prunable, SoftDeletes;

    public function softPrunable()
    {
        return $this->where('id', '<=', 3000);
    }
}

class SoftPrunableWithoutSoftDeletesTestModel extends Model
{
    use Prunable;

    public function softPrunable()
    {
        return $this->where('id', '<=', 50);
    }
}

class SoftPrunableWithHookTestModel extends Model
{
    use Prunable, SoftDeletes;

    public static $pruned = 0;

    public function softPrunable()
    {
        return $this->where('id', '<=', 1000);
    }

    protected function softPruning()
    {
        static::$pruned++;
    }
}

class PrunableWithSoftHookTestModel extends Model
{
    use Prunable, SoftDeletes;

    public static $pruned = 0;

    public function prunable()
    {
        return $this->where('id', '<=', 1000);
    }

    protected function softPruning()
    {
        static::$pruned++;
    }
}

class PrunableBothWindowTestModel extends Model
{
    use Prunable, SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 1000);
    }

    public function softPrunable()
    {
        return $this->whereBetween('id', [1001, 3000]);
    }
}
