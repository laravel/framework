<?php

namespace Illuminate\Tests\Database;


use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Filterable;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseFilterableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        tap(new DB)->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ])->bootEloquent();
    }


    protected function tearDown(): void
    {
        parent::tearDown();

        Model::unsetConnectionResolver();
    }


    public function test_filter_namespace_added_to_model_property(): void
    {
        $model = new FilterableModel;

        $this->assertEquals('App\Filters\\', $model->getFilterNamespace());
    }

    public function test_dont_apply_any_filter_if_filter_class_is_not_exists(): void
    {
        $model = new FilterableModel;

        $query = $model->newQuery()->filter(['UserStatus' => 'approved']);

        $this->assertEquals('select * from "filterable_models"', $query->toSql());
    }


    public function test_dont_apply_any_filter_if_filter_method_is_not_exists(): void
    {
        $model = new FilterableModel;

        $query = $model->newQuery()->filter(['Foo' => 'approved']);

        $this->assertEquals('select * from "filterable_models"', $query->toSql());
    }


    public function test_filter_query(): void
    {
        $model = new FilterableModel;

        $query = $model->newQuery()->filter(['UserCategory' => 'student']);

        $this->assertEquals('select * from "filterable_models" where "category" = ?', $query->toSql());

        $this->assertEquals(['student'], $query->getBindings());
    }


    public function test_filter_query_with_custom_method(): void
    {
        $model = new FilterableModel;

        $query = $model->newQuery()->filter(['order' => 'newest']);

        $this->assertEquals('select * from "filterable_models" order by "created_at" desc', $query->toSql());
    }
}

/**
 * Make Test Model uses Filterable trait
 */
class FilterableModel extends Model
{
    use Filterable;
}


/**
 * Simulating filter classes with proper namespace
 */

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserCategory
{
    public static function apply(Builder $builder, string $param): void
    {
        $builder->where("category", $param);
    }
}

class Order
{
    public static function newest(Builder $builder): void
    {
        $builder->orderBy("created_at", 'desc');
    }
}

class Foo {}
