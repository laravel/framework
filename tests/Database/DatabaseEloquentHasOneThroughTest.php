<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class DatabaseEloquentHasOneThroughTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('setRelation')->once()->with('foo', null);

        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

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
}

class EloquentHasOneThroughModelStub extends Illuminate\Database\Eloquent\Model
{
}
