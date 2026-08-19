<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelLoadCountTest;

use DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\LoadCountBaseModel as BaseModel;
use Illuminate\Tests\App\Models\Relationships\LoadCountDeletedRelated as DeletedRelated;
use Illuminate\Tests\App\Models\Relationships\LoadCountRelated1 as Related1;
use Illuminate\Tests\App\Models\Relationships\LoadCountRelated2 as Related2;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelLoadCountTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
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
