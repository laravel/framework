<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class DatabaseEloquentWithCountTest extends DatabaseTestCase
{
    public function testWithCountSum()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount(['foo' => function ($query) {
            $query->select(DB::raw('sum(num1)'));
        }]);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select sum(num1) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountSumRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount(['foo as foo_sum' => function ($query) {
            $query->select(DB::raw('sum(num1)'));
        }]);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select sum(num1) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_sum" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountAvg()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount(['foo' => function ($query) {
            $query->select(DB::raw('avg(num1)'));
        }]);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select avg(num1) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountAvgRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount(['foo as foo_avg' => function ($query) {
            $query->select(DB::raw('avg(num1)'));
        }]);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select avg(num1) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_avg" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }
}


class EloquentBuilderTestModelParentStub extends Model
{
    public function foo()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class);
    }

    public function address()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class, 'foo_id');
    }

    public function activeFoo()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class, 'foo_id')->where('active', true);
    }
}

class EloquentBuilderTestModelCloseRelatedStub extends Model
{
    public function bar()
    {
        return $this->hasMany(EloquentBuilderTestModelFarRelatedStub::class);
    }

    public function baz()
    {
        return $this->hasMany(EloquentBuilderTestModelFarRelatedStub::class);
    }
}

class EloquentBuilderTestModelFarRelatedStub extends Model
{
    //
}
