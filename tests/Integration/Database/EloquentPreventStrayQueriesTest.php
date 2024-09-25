<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\StrayQueryException;
use Illuminate\Support\Facades\Schema;

class EloquentPreventStrayQueriesTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventStrayQueries();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Model::preventStrayQueries(false);
    }

    protected function afterRefreshingDatabase()
    {
        Schema::create('stray_queries_test_model', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('number')->default(1);
        });
    }

    public function testAnExceptionIsThrownIfTheModelExists()
    {
        $model = EloquentPreventStrayQueriesTestModel::create();

        $this->expectException(StrayQueryException::class);
        $this->expectExceptionMessage('Stray query detected');

        $model->each(function ($model) {
            //
        });
    }

    public function testAnExceptionIsNotThrownIfTheModelDoesNotExist()
    {
        $model = EloquentPreventStrayQueriesTestModel::make(['number' => 5]);

        $model->each(function ($model) {
            $this->assertInstanceOf(EloquentPreventStrayQueriesTestModel::class, $model);
        });
    }

    public function testBadMethodCallExceptionIsStillThrownIfMethodDoesNotExist()
    {
        $model = EloquentPreventStrayQueriesTestModel::create();

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method');

        $model->nonExistingMethod(function ($model) {
            //
        });
    }
}

class EloquentPreventStrayQueriesTestModel extends Model
{
    public $table = 'stray_queries_test_model';
    public $timestamps = false;
    protected $guarded = [];
}
