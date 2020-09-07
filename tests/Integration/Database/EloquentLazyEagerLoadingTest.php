<?php

namespace Illuminate\Tests\Integration\Database\EloquentLazyEagerLoadingTest;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentLazyEagerLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('one', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('two', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('three', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('four', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });
    }

    public function testItBasic()
    {
        $one = Model1::create();
        $one->twos()->create();
        $one->threes()->create();

        $model = Model1::find($one->id);

        $this->assertTrue($model->relationLoaded('twos'));
        $this->assertFalse($model->relationLoaded('threes'));

        DB::enableQueryLog();

        $model->load('threes');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertTrue($model->relationLoaded('threes'));
    }

    public function testWhenCallbackWithReturn()
    {
        $one = Model1::create();
        $one->threes()->create();

        $model = Model1::find($one->id);

        $callback = function ($model, $condition) {
            $this->assertTrue($condition);
            return $model->load('threes');
        };

        $model->when(false, $callback);
        $this->assertFalse($model->relationLoaded('threes'));

        $model->when(true, $callback);
        $this->assertTrue($model->relationLoaded('threes'));
    }

    public function testWhenCallbackWithDefault()
    {
        $one = Model1::create();
        $one->threes()->create();
        $one->fours()->create();

        $model = Model1::find($one->id);

        $callback = function ($model, $condition) { 
            $this->assertTrue($condition);
            return $model->load('threes');
        };

        $default = function ($model, $condition) {
            $this->assertFalse($condition);
            return $model->load('fours');
        };

        $model->when(false, $callback, $default);
        $this->assertTrue($model->relationLoaded('fours'));

        $model->when(true, $callback, $default);
        $this->assertTrue($model->relationLoaded('threes'));
    }
}

class Model1 extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $with = ['twos'];

    public function twos()
    {
        return $this->hasMany(Model2::class, 'one_id');
    }

    public function threes()
    {
        return $this->hasMany(Model3::class, 'one_id');
    }
    
    public function fours()
    {
        return $this->hasMany(Model4::class, 'one_id');
    }
}

class Model2 extends Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function one()
    {
        return $this->belongsTo(Model1::class, 'one_id');
    }
}

class Model3 extends Model
{
    public $table = 'three';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function one()
    {
        return $this->belongsTo(Model1::class, 'one_id');
    }
}

class Model4 extends Model
{
    public $table = 'four';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function one()
    {
        return $this->belongsTo(Model1::class, 'one_id');
    }
}
