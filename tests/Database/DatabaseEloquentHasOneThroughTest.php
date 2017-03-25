<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
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
     * Unit test relation is properly initialized.
     */
    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('setRelation')->once()->with('foo', null);

        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    /**
     * Unit test eager constraints are properly added.
     */
    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('accounts.supplier_id', [1, 2]);
        $model1 = new EloquentHasOneThroughModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneThroughModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    /**
     * Unit test models are properly matched to parents.
     */
    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        // Account Histories
        $results1 = new EloquentHasOneThroughModelStub;
        $results1->supplier_id = 1;
        $results2 = new EloquentHasOneThroughModelStub;
        $results2->supplier_id = 2;

        // Suppliers
        $model1 = new EloquentHasOneThroughModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneThroughModelStub;
        $model2->id = 2;

        $models = $relation->match([$model1, $model2], new Collection([$results1, $results2]), 'foo');

        $this->assertEquals(1, $models[0]->foo->supplier_id);
        $this->assertEquals(2, $models[1]->foo->supplier_id);
    }

    /**
     * Creates a new HasOneThrough relationship of a Supplier having an Account History through an Account.
     *
     * @return HasOneThrough
     */
    protected function getRelation()
    {
        list($builder, $supplier, $account, $parentForeignKey, $relatedForeignKey) = $this->getRelationArguments();

        return new HasOneThrough($builder, $supplier, $account, $parentForeignKey, $relatedForeignKey);
    }

    protected function getRelationArguments()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('join')->once()->with('accounts', 'accounts.id', '=', 'account_histories.account_id');
        $builder->shouldReceive('where')->with('accounts.supplier_id', '=', 1);
        $builder->shouldReceive('whereNotNull')->with('accounts.supplier_id');

        // Suppliers
        $supplier = m::mock('Illuminate\Database\Eloquent\Model');
        $supplier->shouldReceive('getKey')->andReturn(1);
        $supplier->shouldReceive('getKeyName')->andReturn('id');

        // Accounts
        $account = m::mock('Illuminate\Database\Eloquent\Model');
        $account->shouldReceive('getTable')->andReturn('accounts');
        $account->shouldReceive('getQualifiedKeyName')->andReturn('accounts.id');

        // Account Histories
        $accountHistory = m::mock('Illuminate\Database\Eloquent\Model');
        $accountHistory->shouldReceive('getTable')->andReturn('account_histories');

        $builder->shouldReceive('getModel')->andReturn($accountHistory);

        return [$builder, $supplier, $account, 'supplier_id', 'account_id'];
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

// Stub for unit tests
class EloquentHasOneThroughModelStub extends Eloquent
{
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
