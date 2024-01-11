<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelLoadExistsTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelLoadExistsTest extends DatabaseTestCase
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
    }

    public function testLoadExistsSingleRelation()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadExists('related1');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(1, $model->related1_exists);
    }

    public function testLoadExistsMultipleRelations()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadExists(['related1', 'related2']);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(1, $model->related1_exists);
        $this->assertEquals(0, $model->related2_exists);
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
