<?php

namespace Illuminate\Tests\Integration\Database\EloquentLazyEagerLoadingTest;

use DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentLazyEagerLoadingTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('one', function ($table) {
            $table->increments('id');
        });

        Schema::create('two', function ($table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('three', function ($table) {
            $table->increments('id');
            $table->integer('one_id');
        });
    }

    public function test_it_basic()
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
