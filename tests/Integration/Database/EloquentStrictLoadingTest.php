<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class EloquentStrictLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('number')->default(1);
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('model_1_id');
        });

        Schema::create('test_model3', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('model_2_id');
        });
    }

    public function testStrictModeThrowsAnExceptionOnLazyLoading()
    {
        $this->expectException(LazyLoadingViolationException::class);
        $this->expectExceptionMessage('Attempted to lazy load');

        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $models[0]->modelTwos;
    }

    public function testStrictModeDoesntThrowAnExceptionOnLazyLoadingWithSingleModel()
    {
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $this->assertInstanceOf(Collection::class, $models);
    }

    public function testStrictModeDoesntThrowAnExceptionOnAttributes()
    {
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get(['id']);

        $this->assertNull($models[0]->number);
    }

    public function testStrictModeDoesntThrowAnExceptionOnEagerLoading()
    {
        $this->app['config']->set('database.connections.testing.zxc', false);

        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::with('modelTwos')->get();

        $this->assertInstanceOf(Collection::class, $models[0]->modelTwos);
    }

    public function testStrictModeDoesntThrowAnExceptionOnLazyEagerLoading()
    {
        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $models->load('modelTwos');

        $this->assertInstanceOf(Collection::class, $models[0]->modelTwos);
    }

    public function testStrictModeDoesntThrowAnExceptionOnSingleModelLoading()
    {
        $model = EloquentStrictLoadingTestModel1::create();

        $model = EloquentStrictLoadingTestModel1::find($model->id);

        $this->assertInstanceOf(Collection::class, $model->modelTwos);
    }

    public function testStrictModeThrowsAnExceptionOnLazyLoadingInRelations()
    {
        $this->expectException(LazyLoadingViolationException::class);
        $this->expectExceptionMessage('Attempted to lazy load');

        $model1 = EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel2::create(['model_1_id' => $model1->id]);
        EloquentStrictLoadingTestModel2::create(['model_1_id' => $model1->id]);

        $models = EloquentStrictLoadingTestModel1::with('modelTwos')->get();

        $models[0]->modelTwos[0]->modelThrees;
    }

    public function testStrictModeWithCustomCallbackOnLazyLoading()
    {
        Event::fake();

        Model::handleLazyLoadingViolationUsing(function ($model, $key) {
            event(new ViolatedLazyLoadingEvent($model, $key));
        });

        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $models[0]->modelTwos;

        Event::assertDispatched(ViolatedLazyLoadingEvent::class);
    }

    public function testStrictModeWithOverriddenHandlerOnLazyLoading()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Violated');

        EloquentStrictLoadingTestModel1WithCustomHandler::create();
        EloquentStrictLoadingTestModel1WithCustomHandler::create();

        $models = EloquentStrictLoadingTestModel1WithCustomHandler::get();

        $models[0]->modelTwos;
    }

    public function testStrictModeDoesntThrowAnExceptionOnManuallyMadeModel()
    {
        $model1 = EloquentStrictLoadingTestModel1WithLocalPreventsLazyLoading::make();
        $model2 = EloquentStrictLoadingTestModel2::make();
        $model1->modelTwos->push($model2);

        $this->assertInstanceOf(Collection::class, $model1->modelTwos);
    }

    public function testStrictModeDoesntThrowAnExceptionOnRecentlyCreatedModel()
    {
        $model1 = EloquentStrictLoadingTestModel1WithLocalPreventsLazyLoading::create();
        $this->assertInstanceOf(Collection::class, $model1->modelTwos);
    }
}

class EloquentStrictLoadingTestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public function modelTwos()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel2::class, 'model_1_id');
    }
}

class EloquentStrictLoadingTestModel1WithCustomHandler extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public function modelTwos()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel2::class, 'model_1_id');
    }

    protected function handleLazyLoadingViolation($key)
    {
        throw new RuntimeException("Violated {$key}");
    }
}

class EloquentStrictLoadingTestModel1WithLocalPreventsLazyLoading extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    public $preventsLazyLoading = true;
    protected $guarded = [];

    public function modelTwos()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel2::class, 'model_1_id');
    }
}

class EloquentStrictLoadingTestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = [];

    public function modelThrees()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel3::class, 'model_2_id');
    }
}

class EloquentStrictLoadingTestModel3 extends Model
{
    public $table = 'test_model3';
    public $timestamps = false;
    protected $guarded = [];
}

class ViolatedLazyLoadingEvent
{
    public $model;
    public $key;

    public function __construct($model, $key)
    {
        $this->model = $model;
        $this->key = $key;
    }
}
