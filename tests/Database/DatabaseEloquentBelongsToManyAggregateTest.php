<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Expression;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyAggregateTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    public function testWithSumDifferentTables()
    {
        $this->seedData();

        $order = BelongsToManyAggregateTestTestOrder::query()
            ->withSum('products as total_products', 'order_product.quantity')
            ->first();

        $this->assertEquals(12, $order->total_products);
    }

    public function testWithSumSameTable()
    {
        $this->seedData();

        $order = BelongsToManyAggregateTestTestTransaction::query()
            ->withSum('allocatedTo as total_allocated', 'allocations.amount')
            ->first();

        $this->assertEquals(1200, $order->total_allocated);
    }

    public function testWithSumExpression()
    {
        $this->seedData();

        $order = BelongsToManyAggregateTestTestTransaction::query()
            ->withSum('allocatedTo as total_allocated', new Expression('allocations.amount * 2'))
            ->first();

        $this->assertEquals(2400, $order->total_allocated);
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('orders', function ($table) {
            $table->increments('id');
        });

        $this->schema()->create('products', function ($table) {
            $table->increments('id');
        });

        $this->schema()->create('order_product', function ($table) {
            $table->integer('order_id')->unsigned();
            $table->foreign('order_id')->references('id')->on('orders');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->integer('quantity')->unsigned();
        });

        $this->schema()->create('transactions', function ($table) {
            $table->increments('id');
            $table->integer('value')->unsigned();
        });

        $this->schema()->create('allocations', function ($table) {
            $table->integer('from_id')->unsigned();
            $table->foreign('from_id')->references('id')->on('transactions');
            $table->integer('to_id')->unsigned();
            $table->foreign('to_id')->references('id')->on('transactions');
            $table->integer('amount')->unsigned();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('orders');
        $this->schema()->drop('products');
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        $order = BelongsToManyAggregateTestTestOrder::create(['id' => 1]);

        BelongsToManyAggregateTestTestProduct::query()->insert([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        $order->products()->sync([
            1 => ['quantity' => 3],
            2 => ['quantity' => 4],
            3 => ['quantity' => 5],
        ]);

        $transaction = BelongsToManyAggregateTestTestTransaction::create(['id' => 1, 'value' => 1200]);

        BelongsToManyAggregateTestTestTransaction::query()->insert([
            ['id' => 2, 'value' => -300],
            ['id' => 3, 'value' => -400],
            ['id' => 4, 'value' => -500],
        ]);

        $transaction->allocatedTo()->sync([
            2 => ['amount' => 300],
            3 => ['amount' => 400],
            4 => ['amount' => 500],
        ]);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

class BelongsToManyAggregateTestTestOrder extends Eloquent
{
    protected $table = 'orders';
    protected $fillable = ['id'];
    public $timestamps = false;

    public function products()
    {
        return $this
            ->belongsToMany(BelongsToManyAggregateTestTestProduct::class, 'order_product', 'order_id', 'product_id')
            ->withPivot('quantity');
    }
}

class BelongsToManyAggregateTestTestProduct extends Eloquent
{
    protected $table = 'products';
    protected $fillable = ['id'];
    public $timestamps = false;
}

class BelongsToManyAggregateTestTestTransaction extends Eloquent
{
    protected $table = 'transactions';
    protected $fillable = ['id', 'value'];
    public $timestamps = false;

    public function allocatedTo()
    {
        return $this
            ->belongsToMany(BelongsToManyAggregateTestTestTransaction::class, 'allocations', 'from_id', 'to_id')
            ->withPivot('quantity');
    }
}
