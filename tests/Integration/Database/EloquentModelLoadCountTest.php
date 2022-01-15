<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelLoadCountTest;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelLoadCountTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('base_models', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('related1s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
        });

        Schema::create('related2s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
        });

        Schema::create('deleted_related', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
            $table->softDeletes();
        });

        BaseModel::create();

        Related1::create(['base_model_id' => 1]);
        Related1::create(['base_model_id' => 1]);
        Related2::create(['base_model_id' => 1]);
        DeletedRelated::create(['base_model_id' => 1]);
    }

    public function testLoadCountSingleRelation()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadCount('related1');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(2, $model->related1_count);
    }

    public function testLoadCountMultipleRelations()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadCount(['related1', 'related2']);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(2, $model->related1_count);
        $this->assertEquals(1, $model->related2_count);
    }

    public function testLoadCountDeletedRelations()
    {
        $model = BaseModel::first();

        $this->assertNull($model->deletedrelated_count);

        $model->loadCount('deletedrelated');

        $this->assertEquals(1, $model->deletedrelated_count);

        DeletedRelated::first()->delete();

        $model = BaseModel::first();

        $this->assertNull($model->deletedrelated_count);

        $model->loadCount('deletedrelated');

        $this->assertEquals(0, $model->deletedrelated_count);
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

    public function deletedrelated()
    {
        return $this->hasMany(DeletedRelated::class);
    }
}

class Related1 extends Model
{
    public $timestamps = false;

    protected $fillable = ['base_model_id'];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}

class Related2 extends Model
{
    public $timestamps = false;

    protected $fillable = ['base_model_id'];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}

class DeletedRelated extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['base_model_id'];

    public function parent()
    {
        return $this->belongsTo(BaseModel::class);
    }
}
