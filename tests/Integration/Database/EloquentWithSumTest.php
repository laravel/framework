<?php

namespace Illuminate\Tests\Integration\Database\EloquentWithSumTest;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentWithSumTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
            $table->integer('price');
            $table->integer('price2');
        });

        Schema::create('goods', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
            $table->integer('price');
            $table->integer('price2');
        });
    }

    public function testBasic()
    {
        $invoive = Invoice::create(['id' => 1, 'name' => 'text_name']);
        for ($i = 1; $i < 11; $i++) {
            Item::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
            Good::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
        }

        $results = Invoice::withSum('items:price,price2');

        $this->assertEquals([
            ['id' => 1, 'name' => 'text_name', 'items_price_sum' => 55, 'items_price2_sum' => 65],
        ], $results->get()->toArray());
    }

    public function testWithConditions()
    {
        $invoive = Invoice::create(['id' => 1, 'name' => 'text_name']);
        for ($i = 1; $i < 11; $i++) {
            Item::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
            Good::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
        }

        $results = Invoice::withSum(['items:price', 'goods:price,price2' => function (Builder $query) {
            $query->where('price', '>', 6);
        }]);

        $this->assertEquals([
            ['id' => 1, 'name' => 'text_name', 'items_price_sum' => 55, 'goods_price_sum' => 34, 'goods_price2_sum' => 38],
        ], $results->get()->toArray());
    }

    public function testWithSelect()
    {
        $invoive = Invoice::create(['id' => 1, 'name' => 'text_name']);
        for ($i = 1; $i < 11; $i++) {
            Item::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
        }

        $results = Invoice::select(['id'])->withSum('items:price');

        $this->assertEquals([
            ['id' => 1, 'items_price_sum' => 55],
        ], $results->get()->toArray());
    }

    public function testLoadSum()
    {
        $invoive = Invoice::create(['id' => 1, 'name' => 'text_name']);
        for ($i = 1; $i < 11; $i++) {
            Item::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
        }

        $results = Invoice::first();
        $results->loadSum('items:price');

        $this->assertEquals(['id' => 1, 'name' => 'text_name', 'items_price_sum' => 55], $results->toArray());
    }

    public function testLoadSumWithConditions()
    {
        $invoive = Invoice::create(['id' => 1, 'name' => 'text_name']);
        for ($i = 1; $i < 11; $i++) {
            Item::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
        }

        $results = Invoice::first();
        $results->loadSum(['items:price' => function ($query) {
            $query->where('price', '>', 5);
        }]);

        $this->assertEquals(['id' => 1, 'name' => 'text_name', 'items_price_sum' => 40], $results->toArray());
    }

    public function testGlobalScopes()
    {
        $invoive = Invoice::create(['id' => 1, 'name' => 'text_name']);
        for ($i = 1; $i < 11; $i++) {
            Good::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
        }

        $result = Invoice::withSum('goods:price')->first();
        $this->assertEquals(40, $result->goods_price_sum);

        $result = Invoice::withSum('allGoods:price')->first();
        $this->assertEquals(55, $result->all_goods_price_sum);
    }

    public function testSortingScopes()
    {
        $invoive = Invoice::create(['id' => 1, 'name' => 'text_name']);
        for ($i = 1; $i < 11; $i++) {
            Item::create(['invoice_id' => $invoive->id, 'price' => $i, 'price2' => $i + 1]);
        }

        $result = Invoice::withSum('items:price')->toSql();

        $this->assertSame('select "invoices".*, (select sum(price) from "items" where "invoices"."id" = "items"."invoice_id") as "items_price_sum" from "invoices"', $result);
    }
}

class Invoice extends Model
{
    public $table = 'invoices';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(Item::class, 'invoice_id');
    }

    public function goods()
    {
        return $this->hasMany(Good::class, 'invoice_id');
    }

    public function allGoods()
    {
        return $this->goods()->withoutGlobalScopes();
    }
}

class Item extends Model
{
    public $table = 'items';
    public $timestamps = false;
    protected $guarded = ['id'];
}

class Good extends Model
{
    public $table = 'goods';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('price', '>', 5);
        });
    }

}