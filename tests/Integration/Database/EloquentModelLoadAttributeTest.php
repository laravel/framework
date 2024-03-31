<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelLoadSumTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelLoadAttributeTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('base_models', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('related1s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
            $table->string('column_name');
        });

        Schema::create('related2s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
            $table->string('column_name');
        });

        BaseModel::create();

        Related1::create(['base_model_id' => 1, 'column_name' => 'value1']);
        Related1::create(['base_model_id' => 1, 'column_name' => 'value12']);
        Related2::create(['base_model_id' => 1, 'column_name' => 'value2']);
    }

    public function testLoadSumSingleRelation()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadSum('related1', 'column_name');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('value1', $model->related1_attribute_column_name);
    }

    public function testLoadSumMultipleRelations()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadSum(['related1', 'related2'], 'column_name');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('value1', $model->related1_attribute_column_name);
        $this->assertEquals('value2', $model->related2_attribute_column_name);
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

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}

class Related2 extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}
