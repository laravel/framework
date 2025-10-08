<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class EloquentModelTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('nullable_date')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
        });

        // Table for incrementEach / decrementEach tests
        Schema::create('test_increment_each', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('points')->default(0);
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->timestamps();
        });
    }

    /** ----------------------------------------------------------------
     *  Existing tests
     *  ---------------------------------------------------------------- */
    public function testUserCanUpdateNullableDate()
    {
        $user = TestModel1::create(['nullable_date' => null]);
        $user->fill(['nullable_date' => $now = Carbon::now()]);
        $this->assertTrue($user->isDirty('nullable_date'));

        $user->save();
        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
    }

    public function testAttributeChanges()
    {
        $user = TestModel2::create([
            'name' => $originalName = Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $user->name = $overrideName = Str::random();

        $this->assertEquals(['name' => $overrideName], $user->getDirty());
        $user->save();
        $this->assertEquals(['name' => $overrideName], $user->getChanges());
        $this->assertEquals(['name' => $originalName], $user->getPrevious());
    }

    public function testDiscardChanges()
    {
        $user = TestModel2::create([
            'name' => $originalName = Str::random(), 'title' => Str::random(),
        ]);

        $user->name = $overrideName = Str::random();
        $user->discardChanges();

        $this->assertSame($originalName, $user->name);
        $user->save();
        $this->assertFalse($user->wasChanged());
    }

    public function testInsertRecordWithReservedWordFieldName()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamp('start');
            $table->timestamp('end')->nullable();
            $table->boolean('analyze');
        });

        $model = new class extends Model
        {
            protected $table = 'actions';
            protected $guarded = ['id'];
            public $timestamps = false;
        };

        $model->newInstance()->create([
            'label' => 'test',
            'start' => '2023-01-01 00:00:00',
            'end' => '2024-01-01 00:00:00',
            'analyze' => true,
        ]);

        $this->assertDatabaseHas('actions', [
            'label' => 'test',
            'start' => '2023-01-01 00:00:00',
            'end' => '2024-01-01 00:00:00',
            'analyze' => true,
        ]);
    }

    /** ----------------------------------------------------------------
     *  New incrementEach / decrementEach tests
     *  ---------------------------------------------------------------- */
    public function testIncrementEachOnExistingModel()
    {
        $model = TestIncrementEachModel::create(['points' => 5, 'views' => 10, 'likes' => 2]);
        $model->incrementEach(['points' => 3, 'views' => 5]);

        $this->assertEquals(8, $model->fresh()->points);
        $this->assertEquals(15, $model->fresh()->views);
        $this->assertEquals(2, $model->fresh()->likes);
    }

    public function testIncrementEachWithExtraAttributes()
    {
        $model = TestIncrementEachModel::create(['points' => 10, 'views' => 20, 'likes' => 5]);
        $model->incrementEach(['points' => 5], ['likes' => 10]);

        $fresh = $model->fresh();
        $this->assertEquals(15, $fresh->points);
        $this->assertEquals(10, $fresh->likes);
    }

    public function testIncrementEachAffectsOnlyTargetModel()
    {
        $first = TestIncrementEachModel::create(['points' => 5, 'views' => 10]);
        $second = TestIncrementEachModel::create(['points' => 1, 'views' => 1]);

        $first->incrementEach(['points' => 2, 'views' => 3]);

        $this->assertEquals([7, 13], [$first->fresh()->points, $first->fresh()->views]);
        $this->assertEquals([1, 1], [$second->fresh()->points, $second->fresh()->views]);
    }

    public function testIncrementEachOnNonExistentModel()
    {
        $this->expectException(LogicException::class);
        $model = new TestIncrementEachModel(['points' => 5]);
        $model->incrementEach(['points' => 1]);
    }

    public function testIncrementEachWithNonNumericValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $model = TestIncrementEachModel::create(['points' => 5]);
        $model->incrementEach(['points' => 'invalid']);
    }

    public function testIncrementEachWithNonAssociativeArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $model = TestIncrementEachModel::create(['points' => 5]);
        $model->incrementEach([5, 10]);
    }

    public function testDecrementEachOnExistingModel()
    {
        $model = TestIncrementEachModel::create(['points' => 10, 'views' => 20, 'likes' => 5]);
        $model->decrementEach(['points' => 3, 'views' => 5]);

        $fresh = $model->fresh();
        $this->assertEquals(7, $fresh->points);
        $this->assertEquals(15, $fresh->views);
        $this->assertEquals(5, $fresh->likes);
    }

    public function testDecrementEachAffectsOnlyTargetModel()
    {
        $first = TestIncrementEachModel::create(['points' => 10, 'views' => 20]);
        $second = TestIncrementEachModel::create(['points' => 5, 'views' => 5]);

        $first->decrementEach(['points' => 2, 'views' => 3]);

        $this->assertEquals([8, 17], [$first->fresh()->points, $first->fresh()->views]);
        $this->assertEquals([5, 5], [$second->fresh()->points, $second->fresh()->views]);
    }

    protected function tearDown(): void
    {
        \Illuminate\Database\Eloquent\Model::clearBootedModels();
        Schema::dropIfExists('test_increment_each');
        Schema::dropIfExists('actions');
        parent::tearDown();
    }
}

/** ----------------------------------------------------------------
 *  Helper Models
 *  ---------------------------------------------------------------- */
class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['nullable_date' => 'datetime'];
}

class TestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = [];
}

class TestIncrementEachModel extends Model
{
    public $table = 'test_increment_each';
    protected $guarded = [];
}
