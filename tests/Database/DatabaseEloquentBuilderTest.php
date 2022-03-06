<?php

namespace Illuminate\Tests\Database;

use BadMethodCallException;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseEloquentBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);

        m::close();
    }

    public function testFindMethod()
    {
        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $builder->setModel($model);
        $model->shouldReceive('getKeyType')->once()->andReturn('int');
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn('baz');

        $result = $builder->find('bar', ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindManyMethod()
    {
        // ids are not empty
        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $builder->setModel($this->getMockModel());
        $builder->getQuery()->shouldReceive('whereIn')->once()->with('foo_table.foo', ['one', 'two']);
        $builder->shouldReceive('get')->with(['column'])->andReturn(['baz']);

        $result = $builder->findMany(['one', 'two'], ['column']);
        $this->assertEquals(['baz'], $result);

        // ids are empty array
        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->shouldReceive('newCollection')->once()->withNoArgs()->andReturn('emptycollection');
        $builder->setModel($model);
        $builder->getQuery()->shouldNotReceive('whereIn');
        $builder->shouldNotReceive('get');

        $result = $builder->findMany([], ['column']);
        $this->assertSame('emptycollection', $result);

        // ids are empty collection
        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->shouldReceive('newCollection')->once()->withNoArgs()->andReturn('emptycollection');
        $builder->setModel($model);
        $builder->getQuery()->shouldNotReceive('whereIn');
        $builder->shouldNotReceive('get');

        $result = $builder->findMany(collect(), ['column']);
        $this->assertSame('emptycollection', $result);
    }

    public function testFindOrNewMethodModelFound()
    {
        $model = $this->getMockModel();
        $model->shouldReceive('getKeyType')->once()->andReturn('int');
        $model->shouldReceive('findOrNew')->once()->andReturn('baz');

        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
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
        $model->shouldReceive('getKeyType')->once()->andReturn('int');
        $model->shouldReceive('findOrNew')->once()->andReturn(m::mock(Model::class));

        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn(null);

        $result = $model->findOrNew('bar', ['column']);
        $findResult = $builder->find('bar', ['column']);
        $this->assertNull($findResult);
        $this->assertInstanceOf(Model::class, $result);
    }

    public function testFindOrFailMethodThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->shouldReceive('getKeyType')->once()->andReturn('int');
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn(null);
        $builder->findOrFail('bar', ['column']);
    }

    public function testFindOrFailMethodWithManyThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $model = $this->getMockModel();
        $model->shouldReceive('getKey')->andReturn(1);

        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('whereIn')->once()->with('foo_table.foo', [1, 2]);
        $builder->shouldReceive('get')->with(['column'])->andReturn(new Collection([$model]));
        $builder->findOrFail([1, 2], ['column']);
    }

    public function testFindOrFailMethodWithManyUsingCollectionThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $model = $this->getMockModel();
        $model->shouldReceive('getKey')->andReturn(1);

        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('whereIn')->once()->with('foo_table.foo', [1, 2]);
        $builder->shouldReceive('get')->with(['column'])->andReturn(new Collection([$model]));
        $builder->findOrFail(new Collection([1, 2]), ['column']);
    }

    public function testFirstOrFailMethodThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($this->getMockModel());
        $builder->shouldReceive('first')->with(['column'])->andReturn(null);
        $builder->firstOrFail(['column']);
    }

    public function testFindWithMany()
    {
        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->shouldReceive('whereIn')->once()->with('foo_table.foo', [1, 2]);
        $builder->setModel($this->getMockModel());
        $builder->shouldReceive('get')->with(['column'])->andReturn('baz');

        $result = $builder->find([1, 2], ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindWithManyUsingCollection()
    {
        $ids = collect([1, 2]);
        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->shouldReceive('whereIn')->once()->with('foo_table.foo', [1, 2]);
        $builder->setModel($this->getMockModel());
        $builder->shouldReceive('get')->with(['column'])->andReturn('baz');

        $result = $builder->find($ids, ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFirstMethod()
    {
        $builder = m::mock(Builder::class.'[get,take]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('take')->with(1)->andReturnSelf();
        $builder->shouldReceive('get')->with(['*'])->andReturn(new Collection(['bar']));

        $result = $builder->first();
        $this->assertSame('bar', $result);
    }

    public function testQualifyColumn()
    {
        $builder = new Builder(m::mock(BaseBuilder::class));
        $builder->shouldReceive('from')->with('stub');

        $builder->setModel(new EloquentModelStub);

        $this->assertSame('stub.column', $builder->qualifyColumn('column'));
    }

    public function testQualifyColumns()
    {
        $builder = new Builder(m::mock(BaseBuilder::class));
        $builder->shouldReceive('from')->with('stub');

        $builder->setModel(new EloquentModelStub);

        $this->assertEquals(['stub.column', 'stub.name'], $builder->qualifyColumns(['column', 'name']));
    }

    public function testGetMethodLoadsModelsAndHydratesEagerRelations()
    {
        $builder = m::mock(Builder::class.'[getModels,eagerLoadRelations]', [$this->getMockQueryBuilder()]);
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
        $builder = m::mock(Builder::class.'[getModels,eagerLoadRelations]', [$this->getMockQueryBuilder()]);
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
        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $mockModel = new stdClass;
        $mockModel->name = 'foo';
        $builder->shouldReceive('first')->with(['name'])->andReturn($mockModel);

        $this->assertSame('foo', $builder->value('name'));
    }

    public function testValueMethodWithModelNotFound()
    {
        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $builder->shouldReceive('first')->with(['name'])->andReturn(null);

        $this->assertNull($builder->value('name'));
    }

    public function testValueOrFailMethodWithModelFound()
    {
        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $mockModel = new stdClass;
        $mockModel->name = 'foo';
        $builder->shouldReceive('first')->with(['name'])->andReturn($mockModel);

        $this->assertSame('foo', $builder->valueOrFail('name'));
    }

    public function testValueOrFailMethodWithModelNotFoundThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = m::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->shouldReceive('getKeyType')->once()->andReturn('int');
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('where')->once()->with('foo_table.foo', '=', 'bar');
        $builder->shouldReceive('first')->with(['column'])->andReturn(null);
        $builder->whereKey('bar')->valueOrFail('column');
    }

    public function testChunkWithLastChunkComplete()
    {
        $builder = m::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3', 'foo4']);
        $chunk3 = new Collection([]);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(3, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkWithLastChunkPartial()
    {
        $builder = m::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3']);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkCanBeStoppedByReturningFalse()
    {
        $builder = m::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3']);
        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->never()->with(2, 2);
        $builder->shouldReceive('get')->times(1)->andReturn($chunk1);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);

            return false;
        });
    }

    public function testChunkWithCountZero()
    {
        $builder = m::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk = new Collection([]);
        $builder->shouldReceive('forPage')->once()->with(1, 0)->andReturnSelf();
        $builder->shouldReceive('get')->times(1)->andReturn($chunk);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->never();

        $builder->chunk(0, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkPaginatesUsingIdWithLastChunkComplete()
    {
        $builder = m::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10], (object) ['someIdField' => 11]]);
        $chunk3 = new Collection([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 11, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithLastChunkPartial()
    {
        $builder = m::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10]]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->once()->with($chunk2);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithCountZero()
    {
        $builder = m::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk = new Collection([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(0, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(1)->andReturn($chunk);

        $callbackAssertor = m::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->never();

        $builder->chunkById(0, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testLazyWithLastChunkComplete()
    {
        $builder = m::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(3, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn(
            new Collection(['foo1', 'foo2']),
            new Collection(['foo3', 'foo4']),
            new Collection([])
        );

        $this->assertEquals(
            ['foo1', 'foo2', 'foo3', 'foo4'],
            $builder->lazy(2)->all()
        );
    }

    public function testLazyWithLastChunkPartial()
    {
        $builder = m::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('forPage')->once()->with(2, 2)->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn(
            new Collection(['foo1', 'foo2']),
            new Collection(['foo3'])
        );

        $this->assertEquals(
            ['foo1', 'foo2', 'foo3'],
            $builder->lazy(2)->all()
        );
    }

    public function testLazyIsLazy()
    {
        $builder = m::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->shouldReceive('forPage')->once()->with(1, 2)->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(new Collection(['foo1', 'foo2']));

        $this->assertEquals(['foo1', 'foo2'], $builder->lazy(2)->take(2)->all());
    }

    public function testLazyByIdWithLastChunkComplete()
    {
        $builder = m::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10], (object) ['someIdField' => 11]]);
        $chunk3 = new Collection([]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 11, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $this->assertEquals(
            [
                (object) ['someIdField' => 1],
                (object) ['someIdField' => 2],
                (object) ['someIdField' => 10],
                (object) ['someIdField' => 11],
            ],
            $builder->lazyById(2, 'someIdField')->all()
        );
    }

    public function testLazyByIdWithLastChunkPartial()
    {
        $builder = m::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10]]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->times(2)->andReturn($chunk1, $chunk2);

        $this->assertEquals(
            [
                (object) ['someIdField' => 1],
                (object) ['someIdField' => 2],
                (object) ['someIdField' => 10],
            ],
            $builder->lazyById(2, 'someIdField')->all()
        );
    }

    public function testLazyByIdIsLazy()
    {
        $builder = m::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $builder->shouldReceive('forPageAfterId')->once()->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($chunk1);

        $this->assertEquals(
            [
                (object) ['someIdField' => 1],
                (object) ['someIdField' => 2],
            ],
            $builder->lazyById(2, 'someIdField')->take(2)->all()
        );
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

    public function testPluckWithoutModelGetterJustReturnsTheAttributesFoundInDatabase()
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
        $builder = new Builder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));
        $builder->macro('fooBar', function ($builder) {
            $_SERVER['__test.builder'] = $builder;

            return $builder;
        });
        $result = $builder->fooBar();

        $this->assertTrue($builder->hasMacro('fooBar'));
        $this->assertEquals($builder, $result);
        $this->assertEquals($builder, $_SERVER['__test.builder']);
        unset($_SERVER['__test.builder']);
    }

    public function testGlobalMacrosAreCalledOnBuilder()
    {
        Builder::macro('foo', function ($bar) {
            return $bar;
        });

        Builder::macro('bam', function () {
            return $this->getQuery();
        });

        $builder = $this->getBuilder();

        $this->assertTrue(Builder::hasGlobalMacro('foo'));
        $this->assertSame('bar', $builder->foo('bar'));
        $this->assertEquals($builder->bam(), $builder->getQuery());
    }

    public function testMissingStaticMacrosThrowsProperException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method Illuminate\Database\Eloquent\Builder::missingMacro()');

        Builder::missingMacro();
    }

    public function testGetModelsProperlyHydratesModels()
    {
        $builder = m::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $records[] = ['name' => 'taylor', 'age' => 26];
        $records[] = ['name' => 'dayle', 'age' => 28];
        $builder->getQuery()->shouldReceive('get')->once()->with(['foo'])->andReturn(new BaseCollection($records));
        $model = m::mock(Model::class.'[getTable,hydrate]');
        $model->shouldReceive('getTable')->once()->andReturn('foo_table');
        $builder->setModel($model);
        $model->shouldReceive('hydrate')->once()->with($records)->andReturn(new Collection(['hydrated']));
        $models = $builder->getModels(['foo']);

        $this->assertEquals(['hydrated'], $models);
    }

    public function testEagerLoadRelationsLoadTopLevelRelationships()
    {
        $builder = m::mock(Builder::class.'[eagerLoadRelation]', [$this->getMockQueryBuilder()]);
        $nop1 = function () {
            //
        };
        $nop2 = function () {
            //
        };
        $builder->setEagerLoads(['foo' => $nop1, 'foo.bar' => $nop2]);
        $builder->shouldAllowMockingProtectedMethods()->shouldReceive('eagerLoadRelation')->with(['models'], 'foo', $nop1)->andReturn(['foo']);

        $results = $builder->eagerLoadRelations(['models']);
        $this->assertEquals(['foo'], $results);
    }

    public function testRelationshipEagerLoadProcess()
    {
        $builder = m::mock(Builder::class.'[getRelation]', [$this->getMockQueryBuilder()]);
        $builder->setEagerLoads(['orders' => function ($query) {
            $_SERVER['__eloquent.constrain'] = $query;
        }]);
        $relation = m::mock(stdClass::class);
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
        $builder->getModel()->shouldReceive('newInstance->orders')->once()->andReturn($relation = m::mock(stdClass::class));
        $relationQuery = m::mock(stdClass::class);
        $relation->shouldReceive('getQuery')->andReturn($relationQuery);
        $relationQuery->shouldReceive('with')->once()->with(['lines' => null, 'lines.details' => null]);
        $builder->setEagerLoads(['orders' => null, 'orders.lines' => null, 'orders.lines.details' => null]);

        $builder->getRelation('orders');
    }

    public function testGetRelationProperlySetsNestedRelationshipsWithSimilarNames()
    {
        $builder = $this->getBuilder();
        $builder->setModel($this->getMockModel());
        $builder->getModel()->shouldReceive('newInstance->orders')->once()->andReturn($relation = m::mock(stdClass::class));
        $builder->getModel()->shouldReceive('newInstance->ordersGroups')->once()->andReturn($groupsRelation = m::mock(stdClass::class));

        $relationQuery = m::mock(stdClass::class);
        $relation->shouldReceive('getQuery')->andReturn($relationQuery);

        $groupRelationQuery = m::mock(stdClass::class);
        $groupsRelation->shouldReceive('getQuery')->andReturn($groupRelationQuery);
        $groupRelationQuery->shouldReceive('with')->once()->with(['lines' => null, 'lines.details' => null]);

        $builder->setEagerLoads(['orders' => null, 'ordersGroups' => null, 'ordersGroups.lines' => null, 'ordersGroups.lines.details' => null]);

        $builder->getRelation('orders');
        $builder->getRelation('ordersGroups');
    }

    public function testGetRelationThrowsException()
    {
        $this->expectException(RelationNotFoundException::class);

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
        $this->assertInstanceOf(Closure::class, $eagers['orders']);
        $this->assertInstanceOf(Closure::class, $eagers['orders.lines']);

        $builder = $this->getBuilder();
        $builder->with('orders', 'orders.lines');
        $eagers = $builder->getEagerLoads();

        $this->assertEquals(['orders', 'orders.lines'], array_keys($eagers));
        $this->assertInstanceOf(Closure::class, $eagers['orders']);
        $this->assertInstanceOf(Closure::class, $eagers['orders.lines']);

        $builder = $this->getBuilder();
        $builder->with(['orders.lines']);
        $eagers = $builder->getEagerLoads();

        $this->assertEquals(['orders', 'orders.lines'], array_keys($eagers));
        $this->assertInstanceOf(Closure::class, $eagers['orders']);
        $this->assertInstanceOf(Closure::class, $eagers['orders.lines']);

        $builder = $this->getBuilder();
        $builder->with(['orders' => function () {
            return 'foo';
        }]);
        $eagers = $builder->getEagerLoads();

        $this->assertSame('foo', $eagers['orders']());

        $builder = $this->getBuilder();
        $builder->with(['orders.lines' => function () {
            return 'foo';
        }]);
        $eagers = $builder->getEagerLoads();

        $this->assertInstanceOf(Closure::class, $eagers['orders']);
        $this->assertNull($eagers['orders']());
        $this->assertSame('foo', $eagers['orders.lines']());

        $builder = $this->getBuilder();
        $builder->with('orders.lines', function () {
            return 'foo';
        });
        $eagers = $builder->getEagerLoads();

        $this->assertInstanceOf(Closure::class, $eagers['orders']);
        $this->assertNull($eagers['orders']());
        $this->assertSame('foo', $eagers['orders.lines']());
    }

    public function testQueryPassThru()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('foobar')->once()->andReturn('foo');

        $this->assertInstanceOf(Builder::class, $builder->foobar());

        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('insert')->once()->with(['bar'])->andReturn('foo');

        $this->assertSame('foo', $builder->insert(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('insertOrIgnore')->once()->with(['bar'])->andReturn('foo');

        $this->assertSame('foo', $builder->insertOrIgnore(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('insertGetId')->once()->with(['bar'])->andReturn('foo');

        $this->assertSame('foo', $builder->insertGetId(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('insertUsing')->once()->with(['bar'], 'baz')->andReturn('foo');

        $this->assertSame('foo', $builder->insertUsing(['bar'], 'baz'));

        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('raw')->once()->with('bar')->andReturn('foo');

        $this->assertSame('foo', $builder->raw('bar'));

        $builder = $this->getBuilder();
        $grammar = new Grammar;
        $builder->getQuery()->shouldReceive('getGrammar')->once()->andReturn($grammar);
        $this->assertSame($grammar, $builder->getGrammar());
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
        $nestedQuery = m::mock(Builder::class);
        $nestedRawQuery = $this->getMockQueryBuilder();
        $nestedQuery->shouldReceive('getQuery')->once()->andReturn($nestedRawQuery);
        $model = $this->getMockModel()->makePartial();
        $model->shouldReceive('newQueryWithoutRelationships')->once()->andReturn($nestedQuery);
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
        $this->assertSame('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testRealNestedWhereWithScopesMacro()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->where('foo', '=', 'bar')->where(function ($query) {
            $query->where('baz', '>', 9000)->onlyTrashed();
        })->withTrashed();
        $this->assertSame('select * from "table" where "foo" = ? and ("baz" > ? and "table"."deleted_at" is not null)', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testRealNestedWhereWithMultipleScopesAndOneDeadScope()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->empty()->where('foo', '=', 'bar')->empty()->where(function ($query) {
            $query->empty()->where('baz', '>', 9000);
        });
        $this->assertSame('select * from "table" where "foo" = ? and ("baz" > ?) and "table"."deleted_at" is null', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testWhereNot()
    {
        $nestedQuery = m::mock(Builder::class);
        $nestedRawQuery = $this->getMockQueryBuilder();
        $nestedQuery->shouldReceive('getQuery')->once()->andReturn($nestedRawQuery);
        $model = $this->getMockModel()->makePartial();
        $model->shouldReceive('newQueryWithoutRelationships')->once()->andReturn($nestedQuery);
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('addNestedWhereQuery')->once()->with($nestedRawQuery, 'and not');
        $nestedQuery->shouldReceive('foo')->once();

        $result = $builder->whereNot(function ($query) {
            $query->foo();
        });
        $this->assertEquals($builder, $result);
    }

    public function testOrWhereNot()
    {
        $nestedQuery = m::mock(Builder::class);
        $nestedRawQuery = $this->getMockQueryBuilder();
        $nestedQuery->shouldReceive('getQuery')->once()->andReturn($nestedRawQuery);
        $model = $this->getMockModel()->makePartial();
        $model->shouldReceive('newQueryWithoutRelationships')->once()->andReturn($nestedQuery);
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->setModel($model);
        $builder->getQuery()->shouldReceive('addNestedWhereQuery')->once()->with($nestedRawQuery, 'or not');
        $nestedQuery->shouldReceive('foo')->once();

        $result = $builder->orWhereNot(function ($query) {
            $query->foo();
        });
        $this->assertEquals($builder, $result);
    }

    public function testRealQueryHigherOrderOrWhereScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhere->two();
        $this->assertSame('select * from "table" where "one" = ? or ("two" = ?)', $query->toSql());
    }

    public function testRealQueryChainedHigherOrderOrWhereScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhere->two()->orWhere->three();
        $this->assertSame('select * from "table" where "one" = ? or ("two" = ?) or ("three" = ?)', $query->toSql());
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

    public function testWhereBelongsTo()
    {
        $related = new EloquentBuilderTestWhereBelongsToStub([
            'id' => 1,
            'parent_id' => 2,
        ]);

        $parent = new EloquentBuilderTestWhereBelongsToStub([
            'id' => 2,
            'parent_id' => 1,
        ]);

        $builder = $this->getBuilder();
        $builder->shouldReceive('from')->with('eloquent_builder_test_where_belongs_to_stubs');
        $builder->setModel($related);
        $builder->getQuery()->shouldReceive('where')->once()->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', '=', 2, 'and');

        $result = $builder->whereBelongsTo($parent);
        $this->assertEquals($result, $builder);

        $builder = $this->getBuilder();
        $builder->shouldReceive('from')->with('eloquent_builder_test_where_belongs_to_stubs');
        $builder->setModel($related);
        $builder->getQuery()->shouldReceive('where')->once()->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', '=', 2, 'and');

        $result = $builder->whereBelongsTo($parent, 'parent');
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

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountAndSelect()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->select('id')->withCount('foo');

        $this->assertSame('select "id", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountAndMergedWheres()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->select('id')->withCount(['activeFoo' => function ($q) {
            $q->where('bam', '>', 'qux');
        }]);

        $this->assertSame('select "id", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "bam" > ? and "active" = ?) as "active_foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
        $this->assertEquals(['qux', true], $builder->getBindings());
    }

    public function testWithCountAndGlobalScope()
    {
        $model = new EloquentBuilderTestModelParentStub;
        EloquentBuilderTestModelCloseRelatedStub::addGlobalScope('withCount', function ($query) {
            return $query->addSelect('id');
        });

        $builder = $model->select('id')->withCount(['foo']);

        // Remove the global scope so it doesn't interfere with any other tests
        EloquentBuilderTestModelCloseRelatedStub::addGlobalScope('withCount', function ($query) {
            //
        });

        $this->assertSame('select "id", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithMin()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withMin('foo', 'price');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select min("eloquent_builder_test_model_close_related_stubs"."price") from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_min_price" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithMinOnBelongsToMany()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withMin('roles', 'id');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select min("eloquent_builder_test_model_far_related_stubs"."id") from "eloquent_builder_test_model_far_related_stubs" inner join "user_role" on "eloquent_builder_test_model_far_related_stubs"."id" = "user_role"."related_id" where "eloquent_builder_test_model_parent_stubs"."id" = "user_role"."self_id") as "roles_min_id" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithMinOnSelfRelated()
    {
        $model = new EloquentBuilderTestModelSelfRelatedStub;

        $sql = $model->withMin('childFoos', 'created_at')->toSql();

        // alias has a dynamic hash, so replace with a static string for comparison
        $alias = 'self_alias_hash';
        $aliasRegex = '/\b(laravel_reserved_\d)(\b|$)/i';

        $sql = preg_replace($aliasRegex, $alias, $sql);

        $this->assertSame('select "self_related_stubs".*, (select min("self_alias_hash"."created_at") from "self_related_stubs" as "self_alias_hash" where "self_related_stubs"."id" = "self_alias_hash"."parent_id") as "child_foos_min_created_at" from "self_related_stubs"', $sql);
    }

    public function testWithCountAndConstraintsAndHaving()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz');
        $builder->withCount(['foo' => function ($q) {
            $q->where('bam', '>', 'qux');
        }])->having('foo_count', '>=', 1);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "bam" > ?) as "foo_count" from "eloquent_builder_test_model_parent_stubs" where "bar" = ? having "foo_count" >= ?', $builder->toSql());
        $this->assertEquals(['qux', 'baz', 1], $builder->getBindings());
    }

    public function testWithCountAndRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount('foo as foo_bar');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_bar" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithCountMultipleAndPartialRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount(['foo as foo_bar', 'foo']);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_bar", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithExists()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withExists('foo');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_exists" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithExistsAndSelect()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->select('id')->withExists('foo');

        $this->assertSame('select "id", exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_exists" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithExistsAndMergedWheres()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->select('id')->withExists(['activeFoo' => function ($q) {
            $q->where('bam', '>', 'qux');
        }]);

        $this->assertSame('select "id", exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "bam" > ? and "active" = ?) as "active_foo_exists" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
        $this->assertEquals(['qux', true], $builder->getBindings());
    }

    public function testWithExistsAndGlobalScope()
    {
        $model = new EloquentBuilderTestModelParentStub;
        EloquentBuilderTestModelCloseRelatedStub::addGlobalScope('withExists', function ($query) {
            return $query->addSelect('id');
        });

        $builder = $model->select('id')->withExists(['foo']);

        // Remove the global scope so it doesn't interfere with any other tests
        EloquentBuilderTestModelCloseRelatedStub::addGlobalScope('withExists', function ($query) {
            //
        });

        $this->assertSame('select "id", exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_exists" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithExistsOnBelongsToMany()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withExists('roles');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, exists(select * from "eloquent_builder_test_model_far_related_stubs" inner join "user_role" on "eloquent_builder_test_model_far_related_stubs"."id" = "user_role"."related_id" where "eloquent_builder_test_model_parent_stubs"."id" = "user_role"."self_id") as "roles_exists" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithExistsOnSelfRelated()
    {
        $model = new EloquentBuilderTestModelSelfRelatedStub;

        $sql = $model->withExists('childFoos')->toSql();

        // alias has a dynamic hash, so replace with a static string for comparison
        $alias = 'self_alias_hash';
        $aliasRegex = '/\b(laravel_reserved_\d)(\b|$)/i';

        $sql = preg_replace($aliasRegex, $alias, $sql);

        $this->assertSame('select "self_related_stubs".*, exists(select * from "self_related_stubs" as "self_alias_hash" where "self_related_stubs"."id" = "self_alias_hash"."parent_id") as "child_foos_exists" from "self_related_stubs"', $sql);
    }

    public function testWithExistsAndRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withExists('foo as foo_bar');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_bar" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithExistsMultipleAndPartialRename()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withExists(['foo as foo_bar', 'foo']);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_bar", exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_exists" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testHasWithConstraintsAndHavingInSubquery()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz');
        $builder->whereHas('foo', function ($q) {
            $q->having('bam', '>', 'qux');
        })->where('quux', 'quuux');

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? and exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" having "bam" > ?) and "quux" = ?', $builder->toSql());
        $this->assertEquals(['baz', 'qux', 'quuux'], $builder->getBindings());
    }

    public function testHasWithConstraintsWithOrWhereAndHavingInSubquery()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('name', 'larry');
        $builder->whereHas('address', function ($q) {
            $q->where('zipcode', '90210');
            $q->orWhere('zipcode', '90220');
            $q->having('street', '=', 'fooside dr');
        })->where('age', 29);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "name" = ? and exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and ("zipcode" = ? or "zipcode" = ?) having "street" = ?) and "age" = ?', $builder->toSql());
        $this->assertEquals(['larry', '90210', '90220', 'fooside dr', 29], $builder->getBindings());
    }

    public function testHasWithConstraintsAndJoinAndHavingInSubquery()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $builder = $model->where('bar', 'baz');
        $builder->whereHas('foo', function ($q) {
            $q->join('quuuux', function ($j) {
                $j->where('quuuuux', '=', 'quuuuuux');
            });
            $q->having('bam', '>', 'qux');
        })->where('quux', 'quuux');

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? and exists (select * from "eloquent_builder_test_model_close_related_stubs" inner join "quuuux" on "quuuuux" = ? where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" having "bam" > ?) and "quux" = ?', $builder->toSql());
        $this->assertEquals(['baz', 'quuuuuux', 'qux', 'quuux'], $builder->getBindings());
    }

    public function testHasWithConstraintsAndHavingInSubqueryWithCount()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz');
        $builder->whereHas('foo', function ($q) {
            $q->having('bam', '>', 'qux');
        }, '>=', 2)->where('quux', 'quuux');

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? and (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" having "bam" > ?) >= 2 and "quux" = ?', $builder->toSql());
        $this->assertEquals(['baz', 'qux', 'quuux'], $builder->getBindings());
    }

    public function testWithCountAndConstraintsWithBindingInSelectSub()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->newQuery();
        $builder->withCount(['foo' => function ($q) use ($model) {
            $q->selectSub($model->newQuery()->where('bam', '=', 3)->selectRaw('count(0)'), 'bam_3_count');
        }]);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function testWithExistsAndConstraintsWithBindingInSelectSub()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->newQuery();
        $builder->withExists(['foo' => function ($q) use ($model) {
            $q->selectSub($model->newQuery()->where('bam', '=', 3)->selectRaw('count(0)'), 'bam_3_count');
        }]);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, exists(select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_exists" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
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

        $this->assertStringContainsString('"self_alias_hash"."id" = "self_related_stubs"."parent_id"', $sql);
    }

    public function testDoesntHave()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->doesntHave('foo');

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id")', $builder->toSql());
    }

    public function testDoesntHaveNested()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->doesntHave('foo.bar');

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and exists (select * from "eloquent_builder_test_model_far_related_stubs" where "eloquent_builder_test_model_close_related_stubs"."id" = "eloquent_builder_test_model_far_related_stubs"."eloquent_builder_test_model_close_related_stub_id"))', $builder->toSql());
    }

    public function testOrDoesntHave()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz')->orDoesntHave('foo');

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or not exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id")', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testWhereDoesntHave()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->whereDoesntHave('foo', function ($query) {
            $query->where('bar', 'baz');
        });

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "bar" = ?)', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testOrWhereDoesntHave()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('bar', 'baz')->orWhereDoesntHave('foo', function ($query) {
            $query->where('qux', 'quux');
        });

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or not exists (select * from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "qux" = ?)', $builder->toSql());
        $this->assertEquals(['baz', 'quux'], $builder->getBindings());
    }

    public function testWhereMorphedTo()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->whereMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where ("morph_type" = ? and "morph_id" = ?)', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testOrWhereMorphedTo()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->where('bar', 'baz')->orWhereMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or ("morph_type" = ? and "morph_id" = ?)', $builder->toSql());
        $this->assertEquals(['baz', $relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereMorphedToClass()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $builder = $model->whereMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "morph_type" = ?', $builder->toSql());
        $this->assertEquals([EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testWhereMorphedToAlias()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        Relation::morphMap([
            'alias' => EloquentBuilderTestModelCloseRelatedStub::class,
        ]);

        $builder = $model->whereMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "morph_type" = ?', $builder->toSql());
        $this->assertEquals(['alias'], $builder->getBindings());

        Relation::morphMap([], false);
    }

    public function testWhereKeyMethodWithInt()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $int = 1;

        $model->shouldReceive('getKeyType')->once()->andReturn('int');
        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '=', $int);

        $builder->whereKey($int);
    }

    public function testWhereKeyMethodWithStringZero()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $int = 0;

        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '=', (string) $int);

        $builder->whereKey($int);
    }

    public function testWhereKeyMethodWithStringNull()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '=', m::on(function ($argument) {
            return $argument === null;
        }));

        $builder->whereKey(null);
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

    public function testWhereKeyMethodWithModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '=', m::on(function ($argument) {
            return $argument === '1';
        }));

        $builder->whereKey(new class extends Model
        {
            protected $attributes = ['id' => 1];
        });
    }

    public function testWhereKeyNotMethodWithStringZero()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $int = 0;

        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '!=', (string) $int);

        $builder->whereKeyNot($int);
    }

    public function testWhereKeyNotMethodWithStringNull()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '!=', m::on(function ($argument) {
            return $argument === null;
        }));

        $builder->whereKeyNot(null);
    }

    public function testWhereKeyNotMethodWithInt()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $int = 1;

        $model->shouldReceive('getKeyType')->once()->andReturn('int');
        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '!=', $int);

        $builder->whereKeyNot($int);
    }

    public function testWhereKeyNotMethodWithArray()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $array = [1, 2, 3];

        $builder->getQuery()->shouldReceive('whereNotIn')->once()->with($keyName, $array);

        $builder->whereKeyNot($array);
    }

    public function testWhereKeyNotMethodWithCollection()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $collection = new Collection([1, 2, 3]);

        $builder->getQuery()->shouldReceive('whereNotIn')->once()->with($keyName, $collection);

        $builder->whereKeyNot($collection);
    }

    public function testWhereKeyNotMethodWithModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->shouldReceive('where')->once()->with($keyName, '!=', m::on(function ($argument) {
            return $argument === '1';
        }));

        $builder->whereKeyNot(new class extends Model
        {
            protected $attributes = ['id' => 1];
        });
    }

    public function testWhereIn()
    {
        $model = new EloquentBuilderTestNestedStub;
        $this->mockConnectionForModel($model, '');
        $query = $model->newQuery()->withoutGlobalScopes()->whereIn('foo', $model->newQuery()->select('id'));
        $expected = 'select * from "table" where "foo" in (select "id" from "table" where "table"."deleted_at" is null)';
        $this->assertEquals($expected, $query->toSql());
    }

    public function testLatestWithoutColumnWithCreatedAt()
    {
        $model = $this->getMockModel();
        $model->shouldReceive('getCreatedAtColumn')->andReturn('foo');
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->shouldReceive('latest')->once()->with('foo');

        $builder->latest();
    }

    public function testLatestWithoutColumnWithoutCreatedAt()
    {
        $model = $this->getMockModel();
        $model->shouldReceive('getCreatedAtColumn')->andReturn(null);
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->shouldReceive('latest')->once()->with('created_at');

        $builder->latest();
    }

    public function testLatestWithColumn()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->shouldReceive('latest')->once()->with('foo');

        $builder->latest('foo');
    }

    public function testOldestWithoutColumnWithCreatedAt()
    {
        $model = $this->getMockModel();
        $model->shouldReceive('getCreatedAtColumn')->andReturn('foo');
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->shouldReceive('oldest')->once()->with('foo');

        $builder->oldest();
    }

    public function testOldestWithoutColumnWithoutCreatedAt()
    {
        $model = $this->getMockModel();
        $model->shouldReceive('getCreatedAtColumn')->andReturn(null);
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->shouldReceive('oldest')->once()->with('created_at');

        $builder->oldest();
    }

    public function testOldestWithColumn()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->shouldReceive('oldest')->once()->with('foo');

        $builder->oldest('foo');
    }

    public function testUpdate()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = new BaseBuilder(m::mock(ConnectionInterface::class), new Grammar, m::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->shouldReceive('update')->once()
            ->with('update "table" set "foo" = ?, "table"."updated_at" = ?', ['bar', $now])->andReturn(1);

        $result = $builder->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithTimestampValue()
    {
        $query = new BaseBuilder(m::mock(ConnectionInterface::class), new Grammar, m::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->shouldReceive('update')->once()
            ->with('update "table" set "foo" = ?, "table"."updated_at" = ?', ['bar', null])->andReturn(1);

        $result = $builder->update(['foo' => 'bar', 'updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithQualifiedTimestampValue()
    {
        $query = new BaseBuilder(m::mock(ConnectionInterface::class), new Grammar, m::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->shouldReceive('update')->once()
            ->with('update "table" set "table"."foo" = ?, "table"."updated_at" = ?', ['bar', null])->andReturn(1);

        $result = $builder->update(['table.foo' => 'bar', 'table.updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithoutTimestamp()
    {
        $query = new BaseBuilder(m::mock(ConnectionInterface::class), new Grammar, m::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubWithoutTimestamp;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->shouldReceive('update')->once()
            ->with('update "table" set "foo" = ?', ['bar'])->andReturn(1);

        $result = $builder->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithAlias()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = new BaseBuilder(m::mock(ConnectionInterface::class), new Grammar, m::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->shouldReceive('update')->once()
            ->with('update "table" as "alias" set "foo" = ?, "alias"."updated_at" = ?', ['bar', $now])->andReturn(1);

        $result = $builder->from('table as alias')->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithAliasWithQualifiedTimestampValue()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = new BaseBuilder(m::mock(ConnectionInterface::class), new Grammar, m::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->shouldReceive('update')->once()
            ->with('update "table" as "alias" set "foo" = ?, "alias"."updated_at" = ?', ['bar', null])->andReturn(1);

        $result = $builder->from('table as alias')->update(['foo' => 'bar', 'alias.updated_at' => null]);
        $this->assertEquals(1, $result);

        Carbon::setTestNow(null);
    }

    public function testUpsert()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = m::mock(BaseBuilder::class);
        $query->shouldReceive('from')->with('foo_table')->andReturn('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->shouldReceive('upsert')->once()
            ->with([
                ['email' => 'foo', 'name' => 'bar', 'updated_at' => $now, 'created_at' => $now],
                ['name' => 'bar2', 'email' => 'foo2', 'updated_at' => $now, 'created_at' => $now],
            ], ['email'], ['email', 'name', 'updated_at'])->andReturn(2);

        $result = $builder->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], ['email']);

        $this->assertEquals(2, $result);
    }

    public function testWithCastsMethod()
    {
        $builder = new Builder($this->getMockQueryBuilder());
        $model = $this->getMockModel();
        $builder->setModel($model);

        $model->shouldReceive('mergeCasts')->with(['foo' => 'bar'])->once();
        $builder->withCasts(['foo' => 'bar']);
    }

    public function testClone()
    {
        $query = new BaseBuilder(m::mock(ConnectionInterface::class), new Grammar, m::mock(Processor::class));
        $builder = new Builder($query);
        $builder->select('*')->from('users');
        $clone = $builder->clone()->where('email', 'foo');

        $this->assertNotSame($builder, $clone);
        $this->assertSame('select * from "users"', $builder->toSql());
        $this->assertSame('select * from "users" where "email" = ?', $clone->toSql());
    }

    protected function mockConnectionForModel($model, $database)
    {
        $grammarClass = 'Illuminate\Database\Query\Grammars\\'.$database.'Grammar';
        $processorClass = 'Illuminate\Database\Query\Processors\\'.$database.'Processor';
        $grammar = new $grammarClass;
        $processor = new $processorClass;
        $connection = m::mock(ConnectionInterface::class, ['getQueryGrammar' => $grammar, 'getPostProcessor' => $processor]);
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new BaseBuilder($connection, $grammar, $processor);
        });
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $resolver = m::mock(ConnectionResolverInterface::class, ['connection' => $connection]);
        $class = get_class($model);
        $class::setConnectionResolver($resolver);
    }

    protected function getBuilder()
    {
        return new Builder($this->getMockQueryBuilder());
    }

    protected function getMockModel()
    {
        $model = m::mock(Model::class);
        $model->shouldReceive('getKeyName')->andReturn('foo');
        $model->shouldReceive('getTable')->andReturn('foo_table');
        $model->shouldReceive('getQualifiedKeyName')->andReturn('foo_table.foo');

        return $model;
    }

    protected function getMockQueryBuilder()
    {
        $query = m::mock(BaseBuilder::class);
        $query->shouldReceive('from')->with('foo_table');

        return $query;
    }
}

class EloquentBuilderTestStub extends Model
{
    protected $table = 'table';
}

class EloquentBuilderTestScopeStub extends Model
{
    public function scopeApproved($query)
    {
        $query->where('foo', 'bar');
    }
}

class EloquentBuilderTestHigherOrderWhereScopeStub extends Model
{
    protected $table = 'table';

    public function scopeOne($query)
    {
        $query->where('one', 'foo');
    }

    public function scopeTwo($query)
    {
        $query->where('two', 'bar');
    }

    public function scopeThree($query)
    {
        $query->where('three', 'baz');
    }
}

class EloquentBuilderTestNestedStub extends Model
{
    protected $table = 'table';
    use SoftDeletes;

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

class EloquentBuilderTestPluckDatesStub extends Model
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

class EloquentBuilderTestModelParentStub extends Model
{
    public function foo()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class);
    }

    public function address()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class, 'foo_id');
    }

    public function activeFoo()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class, 'foo_id')->where('active', true);
    }

    public function roles()
    {
        return $this->belongsToMany(
            EloquentBuilderTestModelFarRelatedStub::class,
            'user_role',
            'self_id',
            'related_id'
        );
    }

    public function morph()
    {
        return $this->morphTo();
    }
}

class EloquentBuilderTestModelCloseRelatedStub extends Model
{
    public function bar()
    {
        return $this->hasMany(EloquentBuilderTestModelFarRelatedStub::class);
    }

    public function baz()
    {
        return $this->hasMany(EloquentBuilderTestModelFarRelatedStub::class);
    }
}

class EloquentBuilderTestModelFarRelatedStub extends Model
{
    //
}

class EloquentBuilderTestModelSelfRelatedStub extends Model
{
    protected $table = 'self_related_stubs';

    public function parentFoo()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id', 'parent');
    }

    public function childFoo()
    {
        return $this->hasOne(self::class, 'parent_id', 'id');
    }

    public function childFoos()
    {
        return $this->hasMany(self::class, 'parent_id', 'id', 'children');
    }

    public function parentBars()
    {
        return $this->belongsToMany(self::class, 'self_pivot', 'child_id', 'parent_id', 'parent_bars');
    }

    public function childBars()
    {
        return $this->belongsToMany(self::class, 'self_pivot', 'parent_id', 'child_id', 'child_bars');
    }

    public function bazes()
    {
        return $this->hasMany(EloquentBuilderTestModelFarRelatedStub::class, 'foreign_key', 'id', 'bar');
    }
}

class EloquentBuilderTestStubWithoutTimestamp extends Model
{
    const UPDATED_AT = null;

    protected $table = 'table';
}

class EloquentBuilderTestStubStringPrimaryKey extends Model
{
    public $incrementing = false;

    protected $table = 'foo_table';

    protected $keyType = 'string';
}

class EloquentBuilderTestWhereBelongsToStub extends Model
{
    protected $fillable = [
        'id',
        'parent_id',
    ];

    public function eloquentBuilderTestWhereBelongsToStub()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id', 'parent');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id', 'parent');
    }
}
