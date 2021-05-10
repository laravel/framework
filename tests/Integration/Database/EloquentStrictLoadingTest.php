<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\StrictLoadingViolationException;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentStrictLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
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

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'strict_load' => true
        ]);
    }

    public function testStrictModeThrowsAnExceptionOnLazyLoading()
    {
        $this->expectException(StrictLoadingViolationException::class);
        $this->expectExceptionMessage('Trying to lazy load [modelTwos] in model [Illuminate\Tests\Integration\Database\EloquentStrictLoadingTestModel1] is restricted');

        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $models[0]->modelTwos;
    }

    public function testStrictModeDoesntThrowAnExceptionOnEagerLoading()
    {
        $this->app['config']->set('database.connections.testbench.zxc', false);

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

        $this->assertInstanceOf(Collection::class, $model->modelTwos);
    }

    public function testStrictModeThrowsAnExceptionOnLazyLoadingInRelations()
    {
        $this->expectException(StrictLoadingViolationException::class);
        $this->expectExceptionMessage('Trying to lazy load [modelThrees] in model [Illuminate\Tests\Integration\Database\EloquentStrictLoadingTestModel2] is restricted');

        $model1 = EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel2::create(['model_1_id' => $model1->id]);

        $models = EloquentStrictLoadingTestModel1::with('modelTwos')->get();

        $models[0]->modelTwos[0]->modelThrees;
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
