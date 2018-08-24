<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseEloquentWithAggregateTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');
    }

    public function testWithCount()
    {
        $actual =  Orders::withAggregate('products', 'count', '*')->first();
        $expected = DB::select(DB::raw('select (select count(*) from "product_orders" where "orders"."id" = "product_orders"."order_id") as "products_count" from "orders"'))[0];
        $this->assertEquals($expected->products_count, $actual->products_count);
    }

    public function testWithSum()
    {
        $actual =  Orders::withSum('products', 'qty')->first();
        $expected = DB::select(DB::raw('select (select sum(qty) from "product_orders" where "orders"."id" = "product_orders"."order_id") as "products_sum" from "orders"'))[0]; // sum of qty products in order
        $this->assertEquals($expected->products_sum, $actual->products_sum);
    }

    public function testWithAvg()
    {
        $actual =  Orders::withAvg('products', 'price')->first();
        $expected = DB::select(DB::raw('select (select avg(price) from "product_orders" where "orders"."id" = "product_orders"."order_id") as "products_avg" from "orders"'))[0]; // sum of qty products in order
        $this->assertEquals($expected->products_avg, $actual->products_avg);
    }

    public function testWithMinAndAlias()
    {
        $actual =  Orders::withMin('products as min_price', 'price')->first();
        $expected = DB::select(DB::raw('select (select min(price) from "product_orders" where "orders"."id" = "product_orders"."order_id") as "min_price" from "orders"'))[0]; // sum of qty products in order
        $this->assertEquals($expected->min_price, $actual->min_price);
    }

    public function testWithMaxWithAliasWithWhere()
    {
        $actual =  Orders::withMax(['products as higher_price'=>function($query){
            $query->where('qty', '>', 1);
        }], 'price')->first();
        $expected = DB::select(DB::raw('select (select max(price) from "product_orders" where "orders"."id" = "product_orders"."order_id" and "qty" > 1) as "higher_price" from "orders"'))[0];
        $this->assertEquals($expected->higher_price, $actual->higher_price);
    }

    public function testWithSumPricesAndCountQtyWithAliases()
    {
        $actual =  Orders::withSum('products as order_price', 'price')->withSum('products as order_products_count', 'qty')->withCount('products')->first();
        $expected = DB::select(DB::raw('select (select sum(price) from "product_orders" where "orders"."id" = "product_orders"."order_id") as "order_price", (select sum(qty) from "product_orders" where "orders"."id" = "product_orders"."order_id") as "order_products_count", (select count(*) from "product_orders" where "orders"."id" = "product_orders"."order_id") as "products_count" from "orders"'))[0];
        $this->assertEquals($expected->order_price, $actual->order_price);
        $this->assertEquals($expected->products_count, $actual->products_count);
        $this->assertEquals($expected->order_products_count, $actual->order_products_count);
    }

    public function tearDown()
    {
        $this->artisan('migrate:reset');
        parent::tearDown();

    }

}

class Orders extends Model
{

    protected $fillable = [
        'reference',
    ];

    public function products()
    {
        return $this->hasMany(ProductOrders::class, 'order_id');
    }
}

class ProductOrders extends Model
{
    protected $table = 'product_orders';
    protected $fillable = [
        'name', 'qty', 'price',
    ];
}