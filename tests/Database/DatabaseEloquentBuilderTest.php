<?php

namespace Illuminate\Tests\Database;

use StdClass;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class DatabaseEloquentBuilderTest extends TestCase
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
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
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
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
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
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
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
        $builder->shouldReceive('take')->with(1)->andReturnSelf();
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

    public function testChunkWithLastChunkComplete()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3', 'foo4']);
        $chunk3 = new Collection([]);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(3, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkWithLastChunkPartial()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3']);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkCanBeStoppedByReturningFalse()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3']);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->never()->with(2, 2);
        $builder->shouldReceive('get')->times(1)->andReturn($chunk1);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);

            return false;
        });
    }

    public function testChunkWithCountZero()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk = new Collection([]);
        $builder->shouldReceive('forPage')->once()->with(1, 0)->andReturnSelf();
        $builder->shouldReceive('get')->times(1)->andReturn($chunk);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->never();

        $builder->chunk(0, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkPaginatesUsingIdWithLastChunkComplete()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10], (object) ['someIdField' => 11]]);
        $chunk3 = new Collection([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 11, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithLastChunkPartial()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10]]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithCountZero()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk = new Collection([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(0, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(1)->andReturn($chunk);

        $callbackAssertor = m::mock('StdClass');
        $callbackAssertor->shouldReceive('doSomething')->never();

        $builder->chunkById(0, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
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

    public function testPluckReturnsTheCastedAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('pluck')->with('name', '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('hasGetMutator')->with('name')->andReturn(false);
        $builder->getModel()->shouldReceive('hasCast')->with('name')->andReturn(true);
        $builder->getModel()->shouldReceive('newFromBuilder')->with(['name' => 'bar'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'bar']));
        $builder->getModel()->shouldReceive('newFromBuilder')->with(['name' => 'baz'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'baz']));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck('name')->all());
    }

    public function testPluckReturnsTheDateAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('pluck')->with('created_at', '')->andReturn(new BaseCollection(['2010-01-01 00:00:00', '2011-01-01 00:00:00']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('hasGetMutator')->with('created_at')->andReturn(false);
        $builder->getModel()->shouldReceive('hasCast')->with('created_at')->andReturn(false);
        $builder->getModel()->shouldReceive('getDates')->andReturn(['created_at']);
        $builder->getModel()->shouldReceive('newFromBuilder')->with(['created_at' => '2010-01-01 00:00:00'])->andReturn(new EloquentBuilderTestPluckDatesStub(['created_at' => '2010-01-01 00:00:00']));
        $builder->getModel()->shouldReceive('newFromBuilder')->with(['created_at' => '2011-01-01 00:00:00'])->andReturn(new EloquentBuilderTestPluckDatesStub(['created_at' => '2011-01-01 00:00:00']));

        $this->assertEquals(['date_2010-01-01 00:00:00', 'date_2011-01-01 00:00:00'], $builder->pluck('created_at')->all());
    }

    public function testPluckWithoutModelGetterJustReturnTheAttributesFoundInDatabase()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('pluck')->with('name', '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('hasGetMutator')->with('name')->andReturn(false);
        $builder->getModel()->shouldReceive('hasCast')->with('name')->andReturn(false);
        $builder->getModel()->shouldReceive('getDates')->andReturn(['created_at']);

        $this->assertEquals(['bar', 'baz'], $builder->pluck('name')->all());
    }

    public function testLocalMacrosAreCalledOnBuilder()
    {
        unset($_SERVER['__test.builder']);
        $builder = new \Illuminate\Database\Eloquent\Builder(new \Illuminate\Database\Query\Builder(
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

    public function testGlobalMacrosAreCalledOnBuilder()
    {
        Builder::macro('foo', function ($bar) {
            return $bar;
        });

        $this->assertEquals($this->getBuilder()->foo('bar'), 'bar');
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
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[eagerLoadRelation]', [$this->getMockQueryBuilder()]);
        $nop1 = function () {
        };
        $nop2 = function () {
        };
        $builder->setEagerLoads(['foo' => $nop1, 'foo.bar' => $nop2]);
        $builder->shouldAllowMockingProtectedMethods()->shouldReceive('eagerLoadRelation')->with(['models'], 'foo', $nop1)->andReturn(['foo']);

        $results = $builder->eagerLoadRelations(['models']);
        $this->assertEquals(['foo'], $results);
    }

    public function testRelationshipEagerLoadProcess()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder[getRelation]', [$this->getMockQueryBuilder()]);
        $builder->setEagerLoads(['orders' => function ($query) {
            $_SERVER['__eloquent.constrain'] = $query;
        }]);
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

    /**
     * @expectedException Illuminate\Database\Eloquent\RelationNotFoundException
     */
    public function testGetRelationThrowsException()
    {
        $builder = $this->getBuilder();
        $builder->setModel($this->getMockModel());

        $builder->getRelation('invalid');
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
        $builder->with(['orders' => function () {
            return 'foo';
        }]);
        $eagers = $builder->getEagerLoads();

        $this->assertEquals('foo', $eagers['orders']());

        $builder = $this->getBuilder();
        $builder->with(['orders.lines' => function () {
            return 'foo';
        }]);
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

        $result = $builder->where(function ($query) {
            $query->foo();
        });
        $this->assertEquals($builder, $result);
    }

    public function testRealNestedWhereWithScopes()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->where('foo', '=', 'bar')->where(function ($query) {
            $query->where('baz', '>', 9000);
        });
        $this->assertEquals('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testRealNestedWhereWithMultipleScopesAndOneDeadScope()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->empty()->where('foo', '=', 'bar')->empty()->where(function ($query) {
            $query->empty()->where('baz', '>', 9000);
        });
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

    public function testPostgresOperatorsWhere()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('where')->once()->with('foo', '@>', 'bar');
        $result = $builder->where('foo', '@>', 'bar');
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

    public function testWithCount()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount('foo');

        $this->assertEquals('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountAndSelect()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->select('id')->withCount('foo');

        $this->assertEquals('select "id", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountAndMergedWheres()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->select('id')->withCount(['activeFoo' => function ($q) {
            $q->where('bam', '>', 'qux');
        }]);

        $this->assertEquals('select "id", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "bam" > ? and "active" = ?) as "active_foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
        $this->assertEquals(['qux', true], $builder->getBindings());
    }

    public function testWithCountAndContraintsAndHaving()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz');
        $builder->withCount(['foo' => function ($q) {
            $q->where('bam', '>', 'qux');
        }])->having('foo_count', '>=', 1);

        $this->assertEquals('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "bam" > ?) as "foo_count" from "eloquent_builder_test_model_parent_stubs" where "bar" = ? having "foo_count" >= ?', $builder->toSql());
        $this->assertEquals(['qux', 'baz', 1], $builder->getBindings());
    }

    public function testWithCountAndRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount('foo as foo_bar');

        $this->assertEquals('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_bar" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountMultipleAndPartialRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount(['foo as foo_bar', 'foo']);

        $this->assertEquals('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_bar", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
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

    public function testHasWithContraintsWithOrWhereAndHavingInSubquery()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('name', 'larry');
        $builder->whereHas('address', function ($q) {
            $q->where('zipcode', '90210');
            $q->orWhere('zipcode', '90220');
            $q->having('street', '=', 'fooside dr');
        })->where('age', 29);

        $this->assertEquals('select * from "eloquent_builder_test_model_parent_stubs" where "name" = ? and exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and ("zipcode" = ? or "zipcode" = ?) having "street" = ?) and "age" = ?', $builder->toSql());
        $this->assertEquals(['larry', '90210', '90220', 'fooside dr', 29], $builder->getBindings());
    }

    public function testHasWithContraintsAndJoinAndHavingInSubquery()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $builder = $model->where('bar', 'baz');
        $builder->whereHas('foo', function ($q) {
            $q->join('quuuux', function ($j) {
                $j->where('quuuuux', '=', 'quuuuuux');
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
        $aliasRegex = '/\b(laravel_reserved_\d)(\b|$)/i';

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
        $aliasRegex = '/\b(laravel_reserved_\d)(\b|$)/i';

        $sql = preg_replace($aliasRegex, $alias, $sql);

        $this->assertContains('"self_alias_hash"."id" = "self_related_stubs"."parent_id"', $sql);
    }

    public function testWhereKeyMethodWithInt()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $int = 1;

        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '=', $int);

        $builder->whereKey($int);
    }

    public function testWhereKeyMethodWithArray()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $array = [1, 2, 3];

        $builder->getQuery()->shouldReceive('whereIn')->once()->with($keyName, $array);

        $builder->whereKey($array);
    }

    public function testWhereKeyMethodWithCollection()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $collection = new Collection([1, 2, 3]);

        $builder->getQuery()->shouldReceive('whereIn')->once()->with($keyName, $collection);

        $builder->whereKey($collection);
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

class EloquentBuilderTestScopeStub extends \Illuminate\Database\Eloquent\Model
{
    public function scopeApproved($query)
    {
        $query->where('foo', 'bar');
    }
}

class EloquentBuilderTestNestedStub extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'table';
    use \Illuminate\Database\Eloquent\SoftDeletes;

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

class EloquentBuilderTestPluckDatesStub extends \Illuminate\Database\Eloquent\Model
{
    protected $attributes;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    protected function asDateTime($value)
    {
        return 'date_'.$value;
    }
}

class EloquentBuilderTestModelParentStub extends \Illuminate\Database\Eloquent\Model
{
    public function foo()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentBuilderTestModelCloseRelatedStub');
    }

    public function address()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentBuilderTestModelCloseRelatedStub', 'foo_id');
    }

    public function activeFoo()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentBuilderTestModelCloseRelatedStub', 'foo_id')->where('active', true);
    }
}

class EloquentBuilderTestModelCloseRelatedStub extends \Illuminate\Database\Eloquent\Model
{
    public function bar()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentBuilderTestModelFarRelatedStub');
    }

    public function baz()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentBuilderTestModelFarRelatedStub');
    }
}

class EloquentBuilderTestModelFarRelatedStub extends \Illuminate\Database\Eloquent\Model
{
}

class EloquentBuilderTestModelSelfRelatedStub extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'self_related_stubs';

    public function parentFoo()
    {
        return $this->belongsTo('Illuminate\Tests\Database\EloquentBuilderTestModelSelfRelatedStub', 'parent_id', 'id', 'parent');
    }

    public function childFoo()
    {
        return $this->hasOne('Illuminate\Tests\Database\EloquentBuilderTestModelSelfRelatedStub', 'parent_id', 'id');
    }

    public function childFoos()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentBuilderTestModelSelfRelatedStub', 'parent_id', 'id', 'children');
    }

    public function parentBars()
    {
        return $this->belongsToMany('Illuminate\Tests\Database\EloquentBuilderTestModelSelfRelatedStub', 'self_pivot', 'child_id', 'parent_id', 'parent_bars');
    }

    public function childBars()
    {
        return $this->belongsToMany('Illuminate\Tests\Database\EloquentBuilderTestModelSelfRelatedStub', 'self_pivot', 'parent_id', 'child_id', 'child_bars');
    }

    public function bazes()
    {
        return $this->hasMany('Illuminate\Tests\Database\EloquentBuilderTestModelFarRelatedStub', 'foreign_key', 'id', 'bar');
    }
}
