<?php

use Mockery as m;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class DatabaseEloquentHasOneThroughTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $db = new DB();

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
        $this->populateDb();
    }

    protected function createSchema()
    {
        $this->schema()->create('suppliers', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('accounts', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->integer('eloquent_has_one_through_model_supplier_id');
            $table->timestamps();
        });

        $this->schema()->create('account_histories', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('eloquent_has_one_through_model_account_id');
            $table->timestamps();
        });
    }

    protected function populateDb()
    {
        // Insert suppliers
        $this->connection()->insert('insert into suppliers (name) values (\'FooBar\'), (\'BarFoo\')');

        // Insert accounts
        $this->connection()->insert(
            'insert into accounts (email, eloquent_has_one_through_model_supplier_id) values (\'foobar@example.com\', ?), (\'barfoo@example.com\', ?)',
            $this->object_array_column($this->connection()->select('select id from suppliers'), 'id')
        );

        // Insert account histories
        $this->connection()->insert(
            'insert into account_histories (title, eloquent_has_one_through_model_account_id) values (\'FooBar\', ?), (\'BarFoo\', ?)',
            $this->object_array_column($this->connection()->select('select id from accounts'), 'id')
        );
    }

    public function tearDown()
    {
        foreach (['default'] as $connection) {
            $this->schema($connection)->drop('account_histories');
            $this->schema($connection)->drop('accounts');
            $this->schema($connection)->drop('suppliers');
        }

        m::close();
    }

    /**
     * Integration test for lazy loading of the $supplier's $accountHistory.
     */
    public function testIntegrationRetrieveLazy()
    {
        $supplier = EloquentHasOneThroughModelSupplier::where('name', 'BarFoo')->first();

        $accountHistory = $supplier->accountHistory;

        $this->assertNotNull($accountHistory);
        $this->assertInstanceOf('EloquentHasOneThroughModelAccountHistory', $accountHistory);
        $this->assertEquals('BarFoo', $accountHistory->title);
    }

    /**
     * Integration test for eager loading of the $supplier's $accountHistory.
     */
    public function testIntegrationRetrieveEager()
    {
        $supplier = EloquentHasOneThroughModelSupplier::where('name', 'FooBar')->with('accountHistory')->first();

        $this->assertNotNull($supplier->accountHistory);
        $this->assertInstanceOf('EloquentHasOneThroughModelAccountHistory', $supplier->accountHistory);
        $this->assertEquals('FooBar', $supplier->accountHistory->title);
    }

    /**
     * Integration test for multiple eager loading of the $supplier's $accountHistory.
     */
    public function testIntegrationRetrieveEagerMultiple()
    {
        $suppliers = EloquentHasOneThroughModelSupplier::with('accountHistory')->get();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $suppliers);
        foreach ($suppliers as $supplier) {
            $this->assertNotNull($supplier->accountHistory);
            $this->assertInstanceOf('EloquentHasOneThroughModelAccountHistory', $supplier->accountHistory);
            $this->assertEquals($supplier->name, $supplier->accountHistory->title);
        }
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }

    /**
     * @param array  $array array of stdClass
     * @param string $property
     *
     * @return array
     */
    protected function object_array_column($array, $property)
    {
        $columns = [];
        foreach ($array as $item) {
            $columns[] = $item->{$property};
        }

        return $columns;
    }
}

// Stubs for integration tests
class EloquentHasOneThroughModelSupplier extends Eloquent
{
    protected $table = 'suppliers';

    public function accountHistory()
    {
        return $this->hasOneThrough('EloquentHasOneThroughModelAccountHistory', 'EloquentHasOneThroughModelAccount');
    }
}
class EloquentHasOneThroughModelAccount extends Eloquent
{
    protected $table = 'accounts';
}
class EloquentHasOneThroughModelAccountHistory extends Eloquent
{
    protected $table = 'account_histories';
}
