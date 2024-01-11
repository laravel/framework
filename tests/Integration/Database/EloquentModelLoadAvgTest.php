<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelLoadAvgTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelLoadAvgTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('base_models', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('related1s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
            $table->integer('number');
        });

        Schema::create('related2s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
            $table->integer('number');
        });

        BaseModel::create();

        Related1::create(['base_model_id' => 1, 'number' => 10]);
        Related1::create(['base_model_id' => 1, 'number' => 11]);
        Related2::create(['base_model_id' => 1, 'number' => 12]);
        Related2::create(['base_model_id' => 1, 'number' => 13]);
    }

    public function testLoadAvgSingleRelation()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadAvg('related1', 'number');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(10.5, $model->related1_avg_number);
    }

    public function testLoadAvgMultipleRelations()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadAvg(['related1', 'related2'], 'number');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(10.5, $model->related1_avg_number);
        $this->assertEquals(12.5, $model->related2_avg_number);
    }
}

class BaseModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function related1()
    {
        return $this->hasMany(Related1::class);
    }

    public function related2()
    {
        return $this->hasMany(Related2::class);
    }
}

class Related1 extends Model
{
    public $timestamps = false;

    protected $fillable = ['base_model_id', 'number'];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}

class Related2 extends Model
{
    public $timestamps = false;

    protected $fillable = ['base_model_id', 'number'];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}
