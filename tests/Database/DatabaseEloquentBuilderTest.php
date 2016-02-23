<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class DatabaseEloquentBuilderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFindMethod()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($this->getMockModel());
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn('baz');

        $result = $builder->find('bar', ['column']);
        $this->assertEquals('baz', $result);
    }

    public function testFindOrNewMethodModelFound()
    {
        $model = $this->getMockModel();
        $model->shouldReceive('findOrNew')->once()->andReturn('baz');

        $builder = m::mock('Illuminate\Database\Eloquent\Builder[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn('baz');

        $expected = $model->findOrNew('bar', ['column']);
        $result = $builder->find('bar', ['column']);
        $this->assertEquals($expected, $result);
    }

    public function testFindOrNewMethodModelNotFound()
    {
        $model = $this->getMockModel();
        $model->shouldReceive('findOrNew')->once()->andReturn(m::mock('Illuminate\Database\Eloquent\Model'));

        $builder = m::mock('Illuminate\Database\Eloquent\Builder[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn(null);

        $result = $model->findOrNew('bar', ['column']);
        $findResult = $builder->find('bar', ['column']);
        $this->assertNull($findResult);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Model', $result);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFindOrFailMethodThrowsModelNotFoundException()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($this->getMockModel());
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn(null);
        $result = $builder->findOrFail('bar', ['column']);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFindOrFailMethodWithManyThrowsModelNotFoundException()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[get]', [$this->getMockQueryBuilder()]);
        $builder->setModel($this->getMockModel());
        $builder->getQuery()->shouldReceive('whereIn')->once()->with('foo_table.foo', [1, 2]);
        $builder->shouldReceive('get')->with(['column'])->andReturn(new Collection([1]));
        $result = $builder->findOrFail([1, 2], ['column']);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFirstOrFailMethodThrowsModelNotFoundException()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($this->getMockModel());
        $builder->shouldReceive('first')->with(['column'])->andReturn(null);
        $result = $builder->firstOrFail(['column']);
    }

    public function testFindWithMany()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->shouldReceive('whereIn')->once()->with('foo_table.foo', [1, 2]);
        $builder->setModel($this->getMockModel());
        $builder->shouldReceive('get')->with(['column'])->andReturn('baz');

        $result = $builder->find([1, 2], ['column']);
        $this->assertEquals('baz', $result);
    }

    public function testFirstMethod()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[get,take]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('take')->with(1)->andReturn($builder);
        $builder->shouldReceive('get')->with(['*'])->andReturn(new Collection(['bar']));

        $result = $builder->first();
        $this->assertEquals('bar', $result);
    }

    public function testGetMethodLoadsModelsAndHydratesEagerRelations()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[getModels,eagerLoadRelations]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('applyScopes')->andReturnSelf();
        $builder->shouldReceive('getModels')->with(['foo'])->andReturn(['bar']);
        $builder->shouldReceive('eagerLoadRelations')->with(['bar'])->andReturn(['bar', 'baz']);
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('newCollection')->with(['bar', 'baz'])->andReturn(new Collection(['bar', 'baz']));

        $results = $builder->get(['foo']);
        $this->assertEquals(['bar', 'baz'], $results->all());
    }

    public function testGetMethodDoesntHydrateEagerRelationsWhenNoResultsAreReturned()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[getModels,eagerLoadRelations]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('applyScopes')->andReturnSelf();
        $builder->shouldReceive('getModels')->with(['foo'])->andReturn([]);
        $builder->shouldReceive('eagerLoadRelations')->never();
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('newCollection')->with([])->andReturn(new Collection([]));

        $results = $builder->get(['foo']);
        $this->assertEquals([], $results->all());
    }

    public function testValueMethodWithModelFound()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[first]', [$this->getMockQueryBuilder()]);
        $mockModel = new StdClass;
        $mockModel->name = 'foo';
        $builder->shouldReceive('first')->with(['name'])->andReturn($mockModel);

        $this->assertEquals('foo', $builder->value('name'));
    }

    public function testValueMethodWithModelNotFound()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[first]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('first')->with(['name'])->andReturn(null);

        $this->assertNull($builder->value('name'));
    }

    public function testChunkExecuteCallbackOverPaginatedRequest()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturn($builder);
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturn($builder);
        $builder->shouldReceive('forPage')->once()->with(3, 2)->andReturn($builder);
        $builder->shouldReceive('get')->times(3)->andReturn(new Collection(['foo1', 'foo2']), new Collection(['foo3']), new Collection([]));

        $callbackExecutionAssertor = m::mock('StdClass');
        $callbackExecutionAssertor->shouldReceive('doSomething')->with('foo1')->once();
        $callbackExecutionAssertor->shouldReceive('doSomething')->with('foo2')->once();
        $callbackExecutionAssertor->shouldReceive('doSomething')->with('foo3')->once();

        $builder->chunk(2, function ($results) use ($callbackExecutionAssertor) {
            foreach ($results as $result) {
                $callbackExecutionAssertor->doSomething($result);
            }
        });
    }

    public function testChunkCanBeStoppedByReturningFalse()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturn($builder);
        $builder->shouldReceive('forPage')->never()->with(2, 2);
        $builder->shouldReceive('get')->times(1)->andReturn(new Collection(['foo1', 'foo2']));

        $callbackExecutionAssertor = m::mock('StdClass');
        $callbackExecutionAssertor->shouldReceive('doSomething')->with('foo1')->once();
        $callbackExecutionAssertor->shouldReceive('doSomething')->with('foo2')->once();
        $callbackExecutionAssertor->shouldReceive('doSomething')->with('foo3')->never();

        $builder->chunk(2, function ($results) use ($callbackExecutionAssertor) {
            foreach ($results as $result) {
                $callbackExecutionAssertor->doSomething($result);
            }

            return false;
        });
    }

    public function testPluckReturnsTheMutatedAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('pluck')->with('name', '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('hasGetMutator')->with('name')->andReturn(true);
        $builder->getModel()->shouldReceive('newFromBuilder')->with(['name' => 'bar'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'bar']));
        $builder->getModel()->shouldReceive('newFromBuilder')->with(['name' => 'baz'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'baz']));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck('name')->all());
    }

    public function testPluckWithoutModelGetterJustReturnTheAttributesFoundInDatabase()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('pluck')->with('name', '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('hasGetMutator')->with('name')->andReturn(false);

        $this->assertEquals(['bar', 'baz'], $builder->pluck('name')->all());
    }

    public function testMacrosAreCalledOnBuilder()
    {
        unset($_SERVER['__test.builder']);
        $builder = new Illuminate\Database\Eloquent\Builder(new Illuminate\Database\Query\Builder(
            m::mock('Illuminate\Database\ConnectionInterface'),
            m::mock('Illuminate\Database\Query\Grammars\Grammar'),
            m::mock('Illuminate\Database\Query\Processors\Processor')
        ));
        $builder->macro('fooBar', function ($builder) {
            $_SERVER['__test.builder'] = $builder;

            return $builder;
        });
        $result = $builder->fooBar();

        $this->assertEquals($builder, $result);
        $this->assertEquals($builder, $_SERVER['__test.builder']);
        unset($_SERVER['__test.builder']);
    }

    public function testGetModelsProperlyHydratesModels()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[get]', [$this->getMockQueryBuilder()]);
        $records[] = ['name' => 'taylor', 'age' => 26];
        $records[] = ['name' => 'dayle', 'age' => 28];
        $builder->getQuery()->shouldReceive('get')->once()->with(['foo'])->andReturn(new BaseCollection($records));
        $model = m::mock('Illuminate\Database\Eloquent\Model[getTable,getConnectionName,hydrate]');
        $model->shouldReceive('getTable')->once()->andReturn('foo_table');
        $builder->setModel($model);
        $model->shouldReceive('getConnectionName')->once()->andReturn('foo_connection');
        $model->shouldReceive('hydrate')->once()->with($records, 'foo_connection')->andReturn(new Collection(['hydrated']));
        $models = $builder->getModels(['foo']);

        $this->assertEquals($models, ['hydrated']);
    }

    public function testEagerLoadRelationsLoadTopLevelRelationships()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[loadRelation]', [$this->getMockQueryBuilder()]);
        $nop1 = function () {};
        $nop2 = function () {};
        $builder->setEagerLoads(['foo' => $nop1, 'foo.bar' => $nop2]);
        $builder->shouldAllowMockingProtectedMethods()->shouldReceive('loadRelation')->with(['models'], 'foo', $nop1)->andReturn(['foo']);

        $results = $builder->eagerLoadRelations(['models']);
        $this->assertEquals(['foo'], $results);
    }

    public function testRelationshipEagerLoadProcess()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[getRelation]', [$this->getMockQueryBuilder()]);
        $builder->setEagerLoads(['orders' => function ($query) { $_SERVER['__eloquent.constrain'] = $query; }]);
        $relation = m::mock('stdClass');
        $relation->shouldReceive('addEagerConstraints')->once()->with(['models']);
        $relation->shouldReceive('initRelation')->once()->with(['models'], 'orders')->andReturn(['models']);
        $relation->shouldReceive('getEager')->once()->andReturn(['results']);
        $relation->shouldReceive('match')->once()->with(['models'], ['results'], 'orders')->andReturn(['models.matched']);
        $builder->shouldReceive('getRelation')->once()->with('orders')->andReturn($relation);
        $results = $builder->eagerLoadRelations(['models']);

        $this->assertEquals(['models.matched'], $results);
        $this->assertEquals($relation, $_SERVER['__eloquent.constrain']);
        unset($_SERVER['__eloquent.constrain']);
    }

    public function testGetRelationProperlySetsNestedRelationships()
    {
        $builder = $this->getBuilder();
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('orders')->once()->andReturn($relation = m::mock('stdClass'));
        $relationQuery = m::mock('stdClass');
        $relation->shouldReceive('getQuery')->andReturn($relationQuery);
        $relationQuery->shouldReceive('with')->once()->with(['lines' => null, 'lines.details' => null]);
        $builder->setEagerLoads(['orders' => null, 'orders.lines' => null, 'orders.lines.details' => null]);

        $relation = $builder->getRelation('orders');
    }

    public function testGetRelationProperlySetsNestedRelationshipsWithSimilarNames()
    {
        $builder = $this->getBuilder();
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('orders')->once()->andReturn($relation = m::mock('stdClass'));
        $builder->getModel()->shouldReceive('ordersGroups')->once()->andReturn($groupsRelation = m::mock('stdClass'));

        $relationQuery = m::mock('stdClass');
        $relation->shouldReceive('getQuery')->andReturn($relationQuery);

        $groupRelationQuery = m::mock('stdClass');
        $groupsRelation->shouldReceive('getQuery')->andReturn($groupRelationQuery);
        $groupRelationQuery->shouldReceive('with')->once()->with(['lines' => null, 'lines.details' => null]);

        $builder->setEagerLoads(['orders' => null, 'ordersGroups' => null, 'ordersGroups.lines' => null, 'ordersGroups.lines.details' => null]);

        $relation = $builder->getRelation('orders');
        $relation = $builder->getRelation('ordersGroups');
    }

    public function testEagerLoadParsingSetsProperRelationships()
    {
        $builder = $this->getBuilder();
        $builder->with(['orders', 'orders.lines']);
        $eagers = $builder->getEagerLoads();

        $this->assertEquals(['orders', 'orders.lines'], array_keys($eagers));
        $this->assertInstanceOf('Closure', $eagers['orders']);
        $this->assertInstanceOf('Closure', $eagers['orders.lines']);

        $builder = $this->getBuilder();
        $builder->with('orders', 'orders.lines');
        $eagers = $builder->getEagerLoads();

        $this->assertEquals(['orders', 'orders.lines'], array_keys($eagers));
        $this->assertInstanceOf('Closure', $eagers['orders']);
        $this->assertInstanceOf('Closure', $eagers['orders.lines']);

        $builder = $this->getBuilder();
        $builder->with(['orders.lines']);
        $eagers = $builder->getEagerLoads();

        $this->assertEquals(['orders', 'orders.lines'], array_keys($eagers));
        $this->assertInstanceOf('Closure', $eagers['orders']);
        $this->assertInstanceOf('Closure', $eagers['orders.lines']);

        $builder = $this->getBuilder();
        $builder->with(['orders' => function () { return 'foo'; }]);
        $eagers = $builder->getEagerLoads();

        $this->assertEquals('foo', $eagers['orders']());

        $builder = $this->getBuilder();
        $builder->with(['orders.lines' => function () { return 'foo'; }]);
        $eagers = $builder->getEagerLoads();

        $this->assertInstanceOf('Closure', $eagers['orders']);
        $this->assertNull($eagers['orders']());
        $this->assertEquals('foo', $eagers['orders.lines']());
    }

    public function testQueryPassThru()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('foobar')->once()->andReturn('foo');

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Builder', $builder->foobar());

        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('insert')->once()->with(['bar'])->andReturn('foo');

        $this->assertEquals('foo', $builder->insert(['bar']));
    }

    public function testQueryScopes()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->getQuery()->shouldReceive('where')->once()->with('foo', 'bar');
        $builder->setModel($model = new EloquentBuilderTestScopeStub);
        $result = $builder->approved();

        $this->assertEquals($builder, $result);
    }

    public function testNestedWhere()
    {
        $nestedQuery = m::mock('Illuminate\Database\Eloquent\Builder');
        $nestedRawQuery = $this->getMockQueryBuilder();
        $nestedQuery->shouldReceive('getQuery')->once()->andReturn($nestedRawQuery);
        $model = $this->getMockModel()->makePartial();
        $model->shouldReceive('newQueryWithoutScopes')->once()->andReturn($nestedQuery);
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('addNestedWhereQuery')->once()->with($nestedRawQuery, 'and');
        $nestedQuery->shouldReceive('foo')->once();

        $result = $builder->where(function ($query) { $query->foo(); });
        $this->assertEquals($builder, $result);
    }

    public function testRealNestedWhereWithScopes()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->where('foo', '=', 'bar')->where(function ($query) { $query->where('baz', '>', 9000); });
        $this->assertEquals('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testRealNestedWhereWithMultipleScopesAndOneDeadScope()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->empty()->where('foo', '=', 'bar')->empty()->where(function ($query) { $query->empty()->where('baz', '>', 9000); });
        $this->assertEquals('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testSimpleWhere()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('where')->once()->with('foo', '=', 'bar');
        $result = $builder->where('foo', '=', 'bar');
        $this->assertEquals($result, $builder);
    }

    public function testDeleteOverride()
    {
        $builder = $this->getBuilder();
        $builder->onDelete(function ($builder) {
            return ['foo' => $builder];
        });
        $this->assertEquals(['foo' => $builder], $builder->delete());
    }

    public function testHasWithContraintsAndHavingInSubquery()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz');
        $builder->whereHas('foo', function ($q) {
            $q->having('bam', '>', 'qux');
        })->where('quux', 'quuux');

        $this->assertEquals('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? and exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" having "bam" > ?) and "quux" = ?', $builder->toSql());
        $this->assertEquals(['baz', 'qux', 'quuux'], $builder->getBindings());
    }

    public function testHasWithContraintsAndJoinAndHavingInSubquery()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $builder = $model->where('bar', 'baz');
        $builder->whereHas('foo', function ($q) {
            $q->join('quuuux', function ($j) {
               $j->on('quuuuux', '=', 'quuuuuux', 'and', true);
            });
            $q->having('bam', '>', 'qux');
        })->where('quux', 'quuux');

        $this->assertEquals('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? and exists (select * from "eloquent_builder_test_model_close_related_stubs" inner join "quuuux" on "quuuuux" = ? where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" having "bam" > ?) and "quux" = ?', $builder->toSql());
        $this->assertEquals(['baz', 'quuuuuux', 'qux', 'quuux'], $builder->getBindings());
    }

    public function testHasWithContraintsAndHavingInSubqueryWithCount()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz');
        $builder->whereHas('foo', function ($q) {
            $q->having('bam', '>', 'qux');
        }, '>=', 2)->where('quux', 'quuux');

        $this->assertEquals('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? and (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" having "bam" > ?) >= 2 and "quux" = ?', $builder->toSql());
        $this->assertEquals(['baz', 'qux', 'quuux'], $builder->getBindings());
    }

    public function testHasNestedWithConstraints()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->whereHas('foo', function ($q) {
            $q->whereHas('bar', function ($q) {
                $q->where('baz', 'bam');
            });
        })->toSql();

        $result = $model->whereHas('foo.bar', function ($q) {
            $q->where('baz', 'bam');
        })->toSql();

        $this->assertEquals($builder, $result);
    }

    public function testHasNested()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->whereHas('foo', function ($q) {
            $q->has('bar');
        });

        $result = $model->has('foo.bar')->toSql();

        $this->assertEquals($builder->toSql(), $result);
    }

    public function testOrHasNested()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->whereHas('foo', function ($q) {
            $q->has('bar');
        })->orWhereHas('foo', function ($q) {
            $q->has('baz');
        });

        $result = $model->has('foo.bar')->orHas('foo.baz')->toSql();

        $this->assertEquals($builder->toSql(), $result);
    }

    public function testSelfHasNested()
    {
        $model = new EloquentBuilderTestModelSelfRelatedStub;

        $nestedSql = $model->whereHas('parentFoo', function ($q) {
            $q->has('childFoo');
        })->toSql();

        $dotSql = $model->has('parentFoo.childFoo')->toSql();

        // alias has a dynamic hash, so replace with a static string for comparison
        $alias = 'self_alias_hash';
        $aliasRegex = '/\b(self_[a-f0-9]{32})(\b|$)/i';

        $nestedSql = preg_replace($aliasRegex, $alias, $nestedSql);
        $dotSql = preg_replace($aliasRegex, $alias, $dotSql);

        $this->assertEquals($nestedSql, $dotSql);
    }

    public function testSelfHasNestedUsesAlias()
    {
        $model = new EloquentBuilderTestModelSelfRelatedStub;

        $sql = $model->has('parentFoo.childFoo')->toSql();

        // alias has a dynamic hash, so replace with a static string for comparison
        $alias = 'self_alias_hash';
        $aliasRegex = '/\b(self_[a-f0-9]{32})(\b|$)/i';

        $sql = preg_replace($aliasRegex, $alias, $sql);

        $this->assertContains('"self_related_stubs"."parent_id" = "self_alias_hash"."id"', $sql);
    }

    protected function mockConnectionForModel($model, $database)
    {
        $grammarClass = 'Illuminate\Database\Query\Grammars\\'.$database.'Grammar';
        $processorClass = 'Illuminate\Database\Query\Processors\\'.$database.'Processor';
        $grammar = new $grammarClass;
        $processor = new $processorClass;
        $connection = m::mock('Illuminate\Database\ConnectionInterface', ['getQueryGrammar' => $grammar, 'getPostProcessor' => $processor]);
        $resolver = m::mock('Illuminate\Database\ConnectionResolverInterface', ['connection' => $connection]);
        $class = get_class($model);
        $class::setConnectionResolver($resolver);
    }

    protected function getBuilder()
    {
        return new Builder($this->getMockQueryBuilder());
    }

    protected function getMockModel()
    {
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('getKeyName')->andReturn('foo');
        $model->shouldReceive('getTable')->andReturn('foo_table');
        $model->shouldReceive('getQualifiedKeyName')->andReturn('foo_table.foo');

        return $model;
    }

    protected function getMockQueryBuilder()
    {
        $query = m::mock('Illuminate\Database\Query\Builder');
        $query->shouldReceive('from')->with('foo_table');

        return $query;
    }
}

class EloquentBuilderTestScopeStub extends Illuminate\Database\Eloquent\Model
{
    public function scopeApproved($query)
    {
        $query->where('foo', 'bar');
    }
}

class EloquentBuilderTestNestedStub extends Illuminate\Database\Eloquent\Model
{
    protected $table = 'table';
    use Illuminate\Database\Eloquent\SoftDeletes;

    public function scopeEmpty($query)
    {
        return $query;
    }
}

class EloquentBuilderTestPluckStub
{
    protected $attributes;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    public function __get($key)
    {
        return 'foo_'.$this->attributes[$key];
    }
}

class EloquentBuilderTestModelParentStub extends Illuminate\Database\Eloquent\Model
{
    public function foo()
    {
        return $this->belongsTo('EloquentBuilderTestModelCloseRelatedStub');
    }
}

class EloquentBuilderTestModelCloseRelatedStub extends Illuminate\Database\Eloquent\Model
{
    public function bar()
    {
        return $this->hasMany('EloquentBuilderTestModelFarRelatedStub');
    }

    public function baz()
    {
        return $this->hasMany('EloquentBuilderTestModelFarRelatedStub');
    }
}

class EloquentBuilderTestModelFarRelatedStub extends Illuminate\Database\Eloquent\Model
{
}

class EloquentBuilderTestModelSelfRelatedStub extends Illuminate\Database\Eloquent\Model
{
    protected $table = 'self_related_stubs';

    public function parentFoo()
    {
        return $this->belongsTo('EloquentBuilderTestModelSelfRelatedStub', 'parent_id', 'id', 'parent');
    }

    public function childFoo()
    {
        return $this->hasOne('EloquentBuilderTestModelSelfRelatedStub', 'parent_id', 'id', 'child');
    }

    public function childFoos()
    {
        return $this->hasMany('EloquentBuilderTestModelSelfRelatedStub', 'parent_id', 'id', 'children');
    }

    public function parentBars()
    {
        return $this->belongsToMany('EloquentBuilderTestModelSelfRelatedStub', 'self_pivot', 'child_id', 'parent_id', 'parent_bars');
    }

    public function childBars()
    {
        return $this->belongsToMany('EloquentBuilderTestModelSelfRelatedStub', 'self_pivot', 'parent_id', 'child_id', 'child_bars');
    }

    public function bazes()
    {
        return $this->hasMany('EloquentBuilderTestModelFarRelatedStub', 'foreign_key', 'id', 'bar');
    }
}
