<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

/**
 * @group one-of-many
 */
class ShowsQueriesTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('products', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('category_id');
        });

        $this->schema()->create('categories', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('products');
    }

    public function testTheQuerieIshownByQueryBuilder()
    {
        ob_start();
        DB::table('products')->where('id', '=', 1)->show()->get();
        $sql = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('select * from "products" where "id" = 1', $sql);

    }

    public function testTheQuerieIshownByEloquentBuilder()
    {
        ob_start();
        ProductTestContract::whereId(1)->show()->get();
        $sql = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('select * from "products" where "id" = 1', $sql);

        ob_start();
        ProductTestContract::whereHas('category')->show();
        $sql = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('select * from "products" where exists (select * from "categories" where "products"."category_id" = "categories"."id")', $sql);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
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

    private function doISeeTheSqlOnView(string $sql)
    {

        $view->assertSee($sql);
    }

}

/**
 * Eloquent Models...
 */
class ProductTestContract extends Eloquent
{
    protected $table   = 'products';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(CategoryTestContract::class);
    }
}

class CategoryTestContract extends Eloquent
{
    protected $table   = 'categories';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(ProductTestContract::class);
    }
}
