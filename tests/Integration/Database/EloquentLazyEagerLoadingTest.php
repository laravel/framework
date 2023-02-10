<?php

namespace Illuminate\Tests\Integration\Database\EloquentLazyEagerLoadingTest;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentLazyEagerLoadingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('one', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('two', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
            $table->float('value');
        });

        Schema::create('three', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
            $table->float('value');
        });
    }

    public function testItBasic()
    {
        $one = Model1::create();
        $one->twos()->create(['value' => 1]);
        $one->twos()->create(['value' => 2]);
        $one->threes()->create(['value' => 3]);
        $one->threes()->create(['value' => 4]);
        $one->threes()->create(['value' => 5]);

        $model = Model1::find($one->id);

        $this->assertTrue($model->relationLoaded('twos'));
        $this->assertFalse($model->relationLoaded('threes'));
        $this->assertEquals(3, $model->twos_sum_value);
        $this->assertEquals(1.5, $model->twos_avg_value);
        $this->assertEquals(1, $model->twos_min_value);
        $this->assertEquals(2, $model->twos_max_value);
        $this->assertEquals(12, $model->threes_sum_value);
        $this->assertEquals(4, $model->threes_avg_value);
        $this->assertEquals(3, $model->threes_min_value);
        $this->assertEquals(5, $model->threes_max_value);

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
    protected $guarded = [];
    protected $with = ['twos'];
    protected $withAggregate = [
        'sum' => [
            'twos:value',
            'threes:value',
        ],

        'avg' => [
            'twos:value',
            'threes:value',
        ],

        'min' => [
            'twos:value',
            'threes:value',
        ],

        'max' => [
            'twos:value',
            'threes:value',
        ],
    ];

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
    protected $guarded = [];

    public function one()
    {
        return $this->belongsTo(Model1::class, 'one_id');
    }
}

class Model3 extends Model
{
    public $table = 'three';
    public $timestamps = false;
    protected $guarded = [];

    public function one()
    {
        return $this->belongsTo(Model1::class, 'one_id');
    }
}
