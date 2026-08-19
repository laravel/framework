<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\EloquentStrictLoadingTestModel1;
use Illuminate\Tests\App\Models\Relationships\EloquentStrictLoadingTestModel1WithCustomHandler;
use Illuminate\Tests\App\Models\Relationships\EloquentStrictLoadingTestModel1WithLocalPreventsLazyLoading;
use Illuminate\Tests\App\Models\Relationships\EloquentStrictLoadingTestModel2;
use Illuminate\Tests\App\Models\Relationships\ViolatedLazyLoadingEvent;
use RuntimeException;

class EloquentStrictLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();
    }

    protected function afterRefreshingDatabase()
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
        $this->expectExceptionObject(new RuntimeException('Violated'));

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
