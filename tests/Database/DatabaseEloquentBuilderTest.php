<?php

namespace Illuminate\Tests\Database;

use BadMethodCallException;
use Closure;
use Illuminate\Database\Connection;
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
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseEloquentBuilderTest extends TestCase
{
    public function testFindMethod()
    {
        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $builder->setModel($model);
        $model->expects('getKeyType')->andReturn('int');
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar');
        $builder->expects('first')->with(['column'])->andReturn('baz');

        $result = $builder->find('bar', ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindSoleMethod()
    {
        $builder = Mockery::mock(Builder::class.'[sole]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $builder->setModel($model);
        $model->expects('getKeyType')->andReturn('int');
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar');
        $builder->expects('sole')->with(['column'])->andReturn('baz');

        $result = $builder->findSole('bar', ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindManyMethod()
    {
        // ids are not empty
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', ['one', 'two']);
        $builder->expects('get')->with(['column'])->andReturn(['baz']);

        $result = $builder->findMany(['one', 'two'], ['column']);
        $this->assertEquals(['baz'], $result);

        // ids are empty array
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('newCollection')->withNoArgs()->andReturn('emptycollection');
        $model->shouldReceive('getKeyType')->andReturn('int');
        $builder->setModel($model);
        $builder->getQuery()->shouldNotReceive('whereIntegerInRaw');
        $builder->shouldNotReceive('get');

        $result = $builder->findMany([], ['column']);
        $this->assertSame('emptycollection', $result);

        // ids are empty collection
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('newCollection')->withNoArgs()->andReturn('emptycollection');
        $builder->setModel($model);
        $builder->getQuery()->shouldNotReceive('whereIn');
        $builder->shouldNotReceive('get');

        $result = $builder->findMany(collect(), ['column']);
        $this->assertSame('emptycollection', $result);
    }

    public function testFindOrNewMethodModelFound()
    {
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $model->expects('findOrNew')->andReturn('baz');

        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar');
        $builder->expects('first')->with(['column'])->andReturn('baz');

        $expected = $model->findOrNew('bar', ['column']);
        $result = $builder->find('bar', ['column']);
        $this->assertEquals($expected, $result);
    }

    public function testFindOrNewMethodModelNotFound()
    {
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $model->expects('findOrNew')->andReturn(Mockery::mock(Model::class));

        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar');
        $builder->expects('first')->with(['column'])->andReturn(null);

        $result = $model->findOrNew('bar', ['column']);
        $findResult = $builder->find('bar', ['column']);
        $this->assertNull($findResult);
        $this->assertInstanceOf(Model::class, $result);
    }

    public function testFindOrFailMethodThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder->setModel($model);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar');
        $builder->expects('first')->with(['column'])->andReturn(null);
        $builder->findOrFail('bar', ['column']);
    }

    public function testFindOrFailMethodThrowsModelNotFoundExceptionWithBackedEnum()
    {
        $exception = new ModelNotFoundException;
        $exception->setModel('Foo', EloquentBuilderTestBackedEnum::Bar);

        $this->assertSame('No query results for model [Foo] bar', $exception->getMessage());
        $this->assertSame(['bar'], $exception->getIds());
    }

    public function testFindOrFailMethodThrowsModelNotFoundExceptionWithUnitEnum()
    {
        $exception = new ModelNotFoundException;
        $exception->setModel('Foo', EloquentBuilderTestUnitEnum::Baz);

        $this->assertSame('No query results for model [Foo] Baz', $exception->getMessage());
        $this->assertSame(['Baz'], $exception->getIds());
    }

    public function testFindOrFailMethodWithManyThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $model = $this->getMockModel();
        $model->expects('getKey')->andReturn(1);
        $model->expects('getKeyType')->andReturn('int');

        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->expects('get')->with(['column'])->andReturn(new Collection([$model]));
        $builder->findOrFail([1, 2], ['column']);
    }

    public function testFindOrFailMethodWithManyUsingCollectionThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $model = $this->getMockModel();
        $model->expects('getKey')->andReturn(1);
        $model->expects('getKeyType')->andReturn('int');

        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->expects('get')->with(['column'])->andReturn(new Collection([$model]));
        $builder->findOrFail(new Collection([1, 2]), ['column']);
    }

    public function testFindOrMethod()
    {
        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('getKeyType')->times(3)->andReturn('int');
        $builder->setModel($model);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 1)->times(2);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 2);
        $builder->expects('first')->andReturn($model);
        $builder->expects('first')->with(['column'])->andReturn($model);
        $builder->expects('first')->andReturn(null);

        $this->assertSame($model, $builder->findOr(1, fn () => 'callback result'));
        $this->assertSame($model, $builder->findOr(1, ['column'], fn () => 'callback result'));
        $this->assertSame('callback result', $builder->findOr(2, fn () => 'callback result'));
    }

    public function testFindOrMethodWithMany()
    {
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model1 = $this->getMockModel();
        $model2 = $this->getMockModel();
        $model1->expects('getKeyType')->times(3)->andReturn('int');
        $model2->shouldReceive('getKeyType')->andReturn('int');
        $builder->setModel($model1);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2])->times(2);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2, 3]);
        $builder->expects('get')->andReturn(new Collection([$model1, $model2]));
        $builder->expects('get')->with(['column'])->andReturn(new Collection([$model1, $model2]));
        $builder->expects('get')->andReturn(null);

        $result = $builder->findOr([1, 2], fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame($model1, $result[0]);
        $this->assertSame($model2, $result[1]);

        $result = $builder->findOr([1, 2], ['column'], fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame($model1, $result[0]);
        $this->assertSame($model2, $result[1]);

        $result = $builder->findOr([1, 2, 3], fn () => 'callback result');
        $this->assertSame('callback result', $result);
    }

    public function testFindOrMethodWithManyUsingCollection()
    {
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model1 = $this->getMockModel();
        $model2 = $this->getMockModel();
        $model1->expects('getKeyType')->times(3)->andReturn('int');
        $model2->shouldReceive('getKeyType')->andReturn('int');
        $builder->setModel($model1);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2])->times(2);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2, 3]);
        $builder->expects('get')->andReturn(new Collection([$model1, $model2]));
        $builder->expects('get')->with(['column'])->andReturn(new Collection([$model1, $model2]));
        $builder->expects('get')->andReturn(null);

        $result = $builder->findOr(new Collection([1, 2]), fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame($model1, $result[0]);
        $this->assertSame($model2, $result[1]);

        $result = $builder->findOr(new Collection([1, 2]), ['column'], fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame($model1, $result[0]);
        $this->assertSame($model2, $result[1]);

        $result = $builder->findOr(new Collection([1, 2, 3]), fn () => 'callback result');
        $this->assertSame('callback result', $result);
    }

    public function testFirstOrFailMethodThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $builder->setModel($this->getMockModel());
        $builder->expects('first')->with(['column'])->andReturn(null);
        $builder->firstOrFail(['column']);
    }

    public function testFindWithMany()
    {
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->setModel($model);
        $builder->expects('get')->with(['column'])->andReturn('baz');

        $result = $builder->find([1, 2], ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindWithManyUsingCollection()
    {
        $ids = collect([1, 2]);
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->setModel($model);
        $builder->expects('get')->with(['column'])->andReturn('baz');

        $result = $builder->find($ids, ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFirstMethod()
    {
        $builder = Mockery::mock(Builder::class.'[get,take]', [$this->getMockQueryBuilder()]);
        $builder->expects('limit')->with(1)->andReturnSelf();
        $builder->expects('get')->with(['*'])->andReturn(new Collection(['bar']));

        $result = $builder->first();
        $this->assertSame('bar', $result);
    }

    public function testQualifyColumn()
    {
        $builder = new Builder(Mockery::mock(BaseBuilder::class));
        $builder->expects('from')->with('foo_table');

        $builder->setModel(new EloquentBuilderTestStubStringPrimaryKey);

        $this->assertSame('foo_table.column', $builder->qualifyColumn('column'));
    }

    public function testQualifyColumns()
    {
        $builder = new Builder(Mockery::mock(BaseBuilder::class));
        $builder->expects('from')->with('foo_table');

        $builder->setModel(new EloquentBuilderTestStubStringPrimaryKey);

        $this->assertEquals(['foo_table.column', 'foo_table.name'], $builder->qualifyColumns(['column', 'name']));
    }

    public function testGetMethodLoadsModelsAndHydratesEagerRelations()
    {
        $builder = Mockery::mock(Builder::class.'[getModels,eagerLoadRelations]', [$this->getMockQueryBuilder()]);
        $builder->expects('getModels')->with(['foo'])->andReturn(['bar']);
        $builder->expects('eagerLoadRelations')->with(['bar'])->andReturn(['bar', 'baz']);
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('newCollection')->with(['bar', 'baz'])->andReturn(new Collection(['bar', 'baz']));

        $results = $builder->get(['foo']);
        $this->assertEquals(['bar', 'baz'], $results->all());
    }

    public function testGetMethodDoesntHydrateEagerRelationsWhenNoResultsAreReturned()
    {
        $builder = Mockery::mock(Builder::class.'[getModels,eagerLoadRelations]', [$this->getMockQueryBuilder()]);
        $builder->expects('getModels')->with(['foo'])->andReturn([]);
        $builder->shouldReceive('eagerLoadRelations')->never();
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('newCollection')->with([])->andReturn(new Collection([]));

        $results = $builder->get(['foo']);
        $this->assertSame([], $results->all());
    }

    public function testValueMethodWithModelFound()
    {
        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $mockModel = new stdClass;
        $mockModel->name = 'foo';
        $builder->expects('first')->with(['name'])->andReturn($mockModel);

        $this->assertSame('foo', $builder->value('name'));
    }

    public function testValueMethodWithModelNotFound()
    {
        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $builder->expects('first')->with(['name'])->andReturn(null);

        $this->assertNull($builder->value('name'));
    }

    public function testValueOrFailMethodWithModelFound()
    {
        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $mockModel = new stdClass;
        $mockModel->name = 'foo';
        $builder->expects('first')->with(['name'])->andReturn($mockModel);

        $this->assertSame('foo', $builder->valueOrFail('name'));
    }

    public function testValueOrFailMethodWithModelNotFoundThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = Mockery::mock(Builder::class.'[first]', [$this->getMockQueryBuilder()]);
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder->setModel($model);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar');
        $builder->expects('first')->with(['column'])->andReturn(null);
        $builder->whereKey('bar')->valueOrFail('column');
    }

    public function testChunkWithLastChunkComplete()
    {
        $builder = Mockery::mock(Builder::class.'[getOffset,getLimit,offset,limit,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3', 'foo4']);
        $chunk3 = new Collection([]);

        $builder->expects('getOffset')->andReturn(null);
        $builder->expects('getLimit')->andReturn(null);
        $builder->expects('offset')->with(0)->andReturnSelf();
        $builder->expects('offset')->with(2)->andReturnSelf();
        $builder->expects('offset')->with(4)->andReturnSelf();
        $builder->expects('limit')->times(3)->with(2)->andReturnSelf();
        $builder->expects('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = Mockery::mock(stdClass::class);
        $callbackAssertor->expects('doSomething')->with($chunk1);
        $callbackAssertor->expects('doSomething')->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkWithLastChunkPartial()
    {
        $builder = Mockery::mock(Builder::class.'[getOffset,getLimit,offset,limit,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3']);
        $builder->expects('getOffset')->andReturn(null);
        $builder->expects('getLimit')->andReturn(null);
        $builder->expects('offset')->with(0)->andReturnSelf();
        $builder->expects('offset')->with(2)->andReturnSelf();
        $builder->expects('limit')->times(2)->with(2)->andReturnSelf();
        $builder->expects('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = Mockery::mock(stdClass::class);
        $callbackAssertor->expects('doSomething')->with($chunk1);
        $callbackAssertor->expects('doSomething')->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        });
    }

    public function testChunkCanBeStoppedByReturningFalse()
    {
        $builder = Mockery::mock(Builder::class.'[getOffset,getLimit,offset,limit,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection(['foo1', 'foo2']);
        $chunk2 = new Collection(['foo3']);

        $builder->expects('getOffset')->andReturn(null);
        $builder->expects('getLimit')->andReturn(null);
        $builder->expects('offset')->with(0)->andReturnSelf();
        $builder->expects('limit')->with(2)->andReturnSelf();
        $builder->expects('get')->times(1)->andReturn($chunk1);

        $callbackAssertor = Mockery::mock(stdClass::class);
        $callbackAssertor->expects('doSomething')->with($chunk1);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk2);

        $builder->chunk(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);

            return false;
        });
    }

    public function testChunkWithCountZero()
    {
        $builder = Mockery::mock(Builder::class.'[getOffset,getLimit,offset,limit,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->expects('getOffset')->andReturn(null);
        $builder->expects('getLimit')->andReturn(null);
        $builder->shouldReceive('offset')->never();
        $builder->shouldReceive('limit')->never();
        $builder->shouldReceive('get')->never();

        $builder->chunk(0, function () {
            $this->fail('Should not be called.');
        });
    }

    public function testChunkPaginatesUsingIdWithLastChunkComplete()
    {
        $builder = Mockery::mock(Builder::class.'[getOffset,getLimit,forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10], (object) ['someIdField' => 11]]);
        $chunk3 = new Collection([]);
        $builder->expects('getOffset')->andReturnNull();
        $builder->expects('getLimit')->andReturnNull();
        $builder->expects('forPageAfterId')->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->expects('forPageAfterId')->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->expects('forPageAfterId')->with(2, 11, 'someIdField')->andReturnSelf();
        $builder->expects('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

        $callbackAssertor = Mockery::mock(stdClass::class);
        $callbackAssertor->expects('doSomething')->with($chunk1);
        $callbackAssertor->expects('doSomething')->with($chunk2);
        $callbackAssertor->shouldReceive('doSomething')->never()->with($chunk3);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithLastChunkPartial()
    {
        $builder = Mockery::mock(Builder::class.'[getOffset,getLimit,forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10]]);
        $builder->expects('getOffset')->andReturnNull();
        $builder->expects('getLimit')->andReturnNull();
        $builder->expects('forPageAfterId')->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->expects('forPageAfterId')->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->expects('get')->times(2)->andReturn($chunk1, $chunk2);

        $callbackAssertor = Mockery::mock(stdClass::class);
        $callbackAssertor->expects('doSomething')->with($chunk1);
        $callbackAssertor->expects('doSomething')->with($chunk2);

        $builder->chunkById(2, function ($results) use ($callbackAssertor) {
            $callbackAssertor->doSomething($results);
        }, 'someIdField');
    }

    public function testChunkPaginatesUsingIdWithCountZero()
    {
        $builder = Mockery::mock(Builder::class.'[getOffset,getLimit,forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->expects('getOffset')->andReturnNull();
        $builder->expects('getLimit')->andReturnNull();
        $builder->shouldReceive('forPageAfterId')->never();
        $builder->shouldReceive('get')->never();

        $callbackAssertor = Mockery::mock(stdClass::class);
        $callbackAssertor->shouldReceive('doSomething')->never();

        $builder->chunkById(0, function () {
            $this->fail('Should never be called.');
        }, 'someIdField');
    }

    public function testLazyWithLastChunkComplete()
    {
        $builder = Mockery::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->expects('forPage')->with(1, 2)->andReturnSelf();
        $builder->expects('forPage')->with(2, 2)->andReturnSelf();
        $builder->expects('forPage')->with(3, 2)->andReturnSelf();
        $builder->expects('get')->times(3)->andReturn(
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
        $builder = Mockery::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->expects('forPage')->with(1, 2)->andReturnSelf();
        $builder->expects('forPage')->with(2, 2)->andReturnSelf();
        $builder->expects('get')->times(2)->andReturn(
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
        $builder = Mockery::mock(Builder::class.'[forPage,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $builder->expects('forPage')->with(1, 2)->andReturnSelf();
        $builder->expects('get')->andReturn(new Collection(['foo1', 'foo2']));

        $this->assertEquals(['foo1', 'foo2'], $builder->lazy(2)->take(2)->all());
    }

    public function testLazyByIdWithLastChunkComplete()
    {
        $builder = Mockery::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10], (object) ['someIdField' => 11]]);
        $chunk3 = new Collection([]);
        $builder->expects('forPageAfterId')->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->expects('forPageAfterId')->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->expects('forPageAfterId')->with(2, 11, 'someIdField')->andReturnSelf();
        $builder->expects('get')->times(3)->andReturn($chunk1, $chunk2, $chunk3);

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
        $builder = Mockery::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $chunk2 = new Collection([(object) ['someIdField' => 10]]);
        $builder->expects('forPageAfterId')->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->expects('forPageAfterId')->with(2, 2, 'someIdField')->andReturnSelf();
        $builder->expects('get')->times(2)->andReturn($chunk1, $chunk2);

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
        $builder = Mockery::mock(Builder::class.'[forPageAfterId,get]', [$this->getMockQueryBuilder()]);
        $builder->getQuery()->orders[] = ['column' => 'foobar', 'direction' => 'asc'];

        $chunk1 = new Collection([(object) ['someIdField' => 1], (object) ['someIdField' => 2]]);
        $builder->expects('forPageAfterId')->with(2, 0, 'someIdField')->andReturnSelf();
        $builder->expects('get')->andReturn($chunk1);

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
        $builder->getQuery()->expects('pluck')->with('name', '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->andReturn(true);
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'bar'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'bar']));
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'baz'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'baz']));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck('name')->all());
    }

    public function testPluckReturnsTheCastedAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with('name', '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->andReturn(false);
        $builder->getModel()->expects('hasCast')->with('name')->andReturn(true);
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'bar'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'bar']));
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'baz'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'baz']));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck('name')->all());
    }

    public function testPluckReturnsTheDateAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with('created_at', '')->andReturn(new BaseCollection(['2010-01-01 00:00:00', '2011-01-01 00:00:00']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('created_at')->andReturn(false);
        $builder->getModel()->expects('hasCast')->with('created_at')->andReturn(false);
        $builder->getModel()->expects('getDates')->andReturn(['created_at']);
        $builder->getModel()->expects('newFromBuilder')->with(['created_at' => '2010-01-01 00:00:00'])->andReturn(new EloquentBuilderTestPluckDatesStub(['created_at' => '2010-01-01 00:00:00']));
        $builder->getModel()->expects('newFromBuilder')->with(['created_at' => '2011-01-01 00:00:00'])->andReturn(new EloquentBuilderTestPluckDatesStub(['created_at' => '2011-01-01 00:00:00']));

        $this->assertEquals(['date_2010-01-01 00:00:00', 'date_2011-01-01 00:00:00'], $builder->pluck('created_at')->all());
    }

    public function testQualifiedPluckReturnsTheMutatedAttributesOfAModel()
    {
        $model = $this->getMockModel();
        $model->expects('qualifyColumn')->times(2)->with('name')->andReturn('foo_table.name');

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with($model->qualifyColumn('name'), '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($model);
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->andReturn(true);
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'bar'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'bar']));
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'baz'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'baz']));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck($model->qualifyColumn('name'))->all());
    }

    public function testQualifiedPluckReturnsTheCastedAttributesOfAModel()
    {
        $model = $this->getMockModel();
        $model->expects('qualifyColumn')->times(2)->with('name')->andReturn('foo_table.name');

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with($model->qualifyColumn('name'), '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($model);
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->andReturn(false);
        $builder->getModel()->expects('hasCast')->with('name')->andReturn(true);
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'bar'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'bar']));
        $builder->getModel()->expects('newFromBuilder')->with(['name' => 'baz'])->andReturn(new EloquentBuilderTestPluckStub(['name' => 'baz']));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck($model->qualifyColumn('name'))->all());
    }

    public function testQualifiedPluckReturnsTheDateAttributesOfAModel()
    {
        $model = $this->getMockModel();
        $model->expects('qualifyColumn')->times(2)->with('created_at')->andReturn('foo_table.created_at');

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with($model->qualifyColumn('created_at'), '')->andReturn(new BaseCollection(['2010-01-01 00:00:00', '2011-01-01 00:00:00']));
        $builder->setModel($model);
        $builder->getModel()->expects('hasAnyGetMutator')->with('created_at')->andReturn(false);
        $builder->getModel()->expects('hasCast')->with('created_at')->andReturn(false);
        $builder->getModel()->expects('getDates')->andReturn(['created_at']);
        $builder->getModel()->expects('newFromBuilder')->with(['created_at' => '2010-01-01 00:00:00'])->andReturn(new EloquentBuilderTestPluckDatesStub(['created_at' => '2010-01-01 00:00:00']));
        $builder->getModel()->expects('newFromBuilder')->with(['created_at' => '2011-01-01 00:00:00'])->andReturn(new EloquentBuilderTestPluckDatesStub(['created_at' => '2011-01-01 00:00:00']));

        $this->assertEquals(['date_2010-01-01 00:00:00', 'date_2011-01-01 00:00:00'], $builder->pluck($model->qualifyColumn('created_at'))->all());
    }

    public function testPluckWithoutModelGetterJustReturnsTheAttributesFoundInDatabase()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with('name', '')->andReturn(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->andReturn(false);
        $builder->getModel()->expects('hasCast')->with('name')->andReturn(false);
        $builder->getModel()->expects('getDates')->andReturn(['created_at']);

        $this->assertEquals(['bar', 'baz'], $builder->pluck('name')->all());
    }

    public function testLocalMacrosAreCalledOnBuilder()
    {
        unset($_SERVER['__test.builder']);
        $builder = new Builder(new BaseBuilder(
            Mockery::mock(ConnectionInterface::class),
            Mockery::mock(Grammar::class),
            Mockery::mock(Processor::class)
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
        $this->expectExceptionObject(new BadMethodCallException('Call to undefined method Illuminate\Database\Eloquent\Builder::missingMacro()'));

        Builder::missingMacro();
    }

    public function testGetModelsProperlyHydratesModels()
    {
        $builder = Mockery::mock(Builder::class.'[get]', [$this->getMockQueryBuilder()]);
        $records[] = ['name' => 'taylor', 'age' => 26];
        $records[] = ['name' => 'dayle', 'age' => 28];
        $builder->getQuery()->expects('get')->with(['foo'])->andReturn(new BaseCollection($records));
        $model = Mockery::mock(Model::class.'[getTable,hydrate]');
        $model->expects('getTable')->andReturn('foo_table');
        $builder->setModel($model);
        $model->expects('hydrate')->with($records)->andReturn(new Collection(['hydrated']));
        $models = $builder->getModels(['foo']);

        $this->assertEquals(['hydrated'], $models);
    }

    public function testEagerLoadRelationsLoadTopLevelRelationships()
    {
        $builder = Mockery::mock(Builder::class.'[eagerLoadRelation]', [$this->getMockQueryBuilder()]);
        $nop1 = function () {
            //
        };
        $nop2 = function () {
            //
        };
        $builder->setEagerLoads(['foo' => $nop1, 'foo.bar' => $nop2]);
        $builder->shouldAllowMockingProtectedMethods()->expects('eagerLoadRelation')->with(['models'], 'foo', $nop1)->andReturn(['foo']);

        $results = $builder->eagerLoadRelations(['models']);
        $this->assertEquals(['foo'], $results);
    }

    public function testEagerLoadRelationsCanBeFlushed()
    {
        $builder = Mockery::mock(Builder::class.'[eagerLoadRelation]', [$this->getMockQueryBuilder()]);

        $builder->setEagerLoads(['foo']);

        $this->assertSame(['foo'], $builder->getEagerLoads());

        $builder->withoutEagerLoads();

        $this->assertEmpty($builder->getEagerLoads());
    }

    public function testRelationshipEagerLoadProcess()
    {
        $builder = Mockery::mock(Builder::class.'[getRelation]', [$this->getMockQueryBuilder()]);
        $builder->setEagerLoads(['orders' => function ($query) {
            $_SERVER['__eloquent.constrain'] = $query;
        }]);
        $relation = Mockery::mock(stdClass::class);
        $relation->expects('addEagerConstraints')->with(['models']);
        $relation->expects('initRelation')->with(['models'], 'orders')->andReturn(['models']);
        $relation->expects('getEager')->andReturn(['results']);
        $relation->expects('match')->with(['models'], ['results'], 'orders')->andReturn(['models.matched']);
        $builder->expects('getRelation')->with('orders')->andReturn($relation);
        $results = $builder->eagerLoadRelations(['models']);

        $this->assertEquals(['models.matched'], $results);
        $this->assertEquals($relation, $_SERVER['__eloquent.constrain']);
        unset($_SERVER['__eloquent.constrain']);
    }

    public function testRelationshipEagerLoadProcessForImplicitlyEmpty()
    {
        $queryBuilder = $this->getMockQueryBuilder();
        $builder = Mockery::mock(Builder::class.'[getRelation]', [$queryBuilder]);
        $builder->setEagerLoads(['parentFoo' => function ($query) {
            $_SERVER['__eloquent.constrain'] = $query;
        }]);
        $model = new EloquentBuilderTestModelSelfRelatedStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $models = [
            new EloquentBuilderTestModelSelfRelatedStub,
            new EloquentBuilderTestModelSelfRelatedStub,
        ];
        $relation = Mockery::mock($model->parentFoo());

        $builder->expects('getRelation')->with('parentFoo')->andReturn($relation);

        $results = $builder->eagerLoadRelations($models);

        unset($_SERVER['__eloquent.constrain']);
    }

    public function testGetRelationProperlySetsNestedRelationships()
    {
        $builder = $this->getBuilder();
        $builder->setModel($this->getMockModel());
        $relation = Mockery::mock(Relation::class);
        $builder->getModel()->expects('newInstance->orders')->andReturn($relation);
        $relationQuery = Mockery::mock(Builder::class);
        $relation->expects('getQuery')->andReturn($relationQuery);
        $relationQuery->expects('with')->with(['lines' => null, 'lines.details' => null]);
        $builder->setEagerLoads(['orders' => null, 'orders.lines' => null, 'orders.lines.details' => null]);

        $builder->getRelation('orders');
    }

    public function testGetRelationProperlySetsNestedRelationshipsWithSimilarNames()
    {
        $builder = $this->getBuilder();
        $builder->setModel($this->getMockModel());
        $relation = Mockery::mock(Relation::class);
        $builder->getModel()->expects('newInstance->orders')->andReturn($relation);
        $groupsRelation = Mockery::mock(Relation::class);
        $builder->getModel()->expects('newInstance->ordersGroups')->andReturn($groupsRelation);

        $relationQuery = Mockery::mock(Builder::class);
        $relation->shouldReceive('getQuery')->andReturn($relationQuery);

        $groupRelationQuery = Mockery::mock(Builder::class);
        $groupsRelation->expects('getQuery')->andReturn($groupRelationQuery);
        $groupRelationQuery->expects('with')->with(['lines' => null, 'lines.details' => null]);

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

        $this->assertSame('foo', $eagers['orders']($this->getBuilder()));

        $builder = $this->getBuilder();
        $builder->with(['orders.lines' => function () {
            return 'foo';
        }]);
        $eagers = $builder->getEagerLoads();

        $this->assertInstanceOf(Closure::class, $eagers['orders']);
        $this->assertNull($eagers['orders']());
        $this->assertSame('foo', $eagers['orders.lines']($this->getBuilder()));

        $builder = $this->getBuilder();
        $builder->with('orders.lines', function () {
            return 'foo';
        });
        $eagers = $builder->getEagerLoads();

        $this->assertInstanceOf(Closure::class, $eagers['orders']);
        $this->assertNull($eagers['orders']());
        $this->assertSame('foo', $eagers['orders.lines']($this->getBuilder()));
    }

    public function testQueryPassThru()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('foobar')->andReturn('foo');

        $this->assertInstanceOf(Builder::class, $builder->foobar());

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insert')->with(['bar'])->andReturn('foo');

        $this->assertSame('foo', $builder->insert(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertOrIgnore')->with(['bar'])->andReturn('foo');

        $this->assertSame('foo', $builder->insertOrIgnore(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertOrIgnoreReturning')->with(['bar'], ['baz'])->andReturn('foo');

        $this->assertSame('foo', $builder->insertOrIgnoreReturning(['bar'], ['baz']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertOrIgnoreUsing')->with(['bar'], 'baz')->andReturn('foo');

        $this->assertSame('foo', $builder->insertOrIgnoreUsing(['bar'], 'baz'));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertGetId')->with(['bar'])->andReturn('foo');

        $this->assertSame('foo', $builder->insertGetId(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertUsing')->with(['bar'], 'baz')->andReturn('foo');

        $this->assertSame('foo', $builder->insertUsing(['bar'], 'baz'));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('raw')->with('bar')->andReturn('foo');

        $this->assertSame('foo', $builder->raw('bar'));
    }

    public function testQueryScopes()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->getQuery()->expects('where')->with('foo', 'bar');
        $builder->setModel($model = new EloquentBuilderTestScopeStub);
        $result = $builder->approved();

        $this->assertEquals($builder, $result);
    }

    public function testQueryDynamicScopes()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->getQuery()->expects('where')->with('bar', 'foo');
        $builder->setModel($model = new EloquentBuilderTestDynamicScopeStub);
        $result = $builder->dynamic('bar', 'foo');

        $this->assertEquals($builder, $result);
    }

    public function testQueryDynamicScopesNamed()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->getQuery()->expects('where')->with('foo', 'foo');
        $builder->setModel($model = new EloquentBuilderTestDynamicScopeStub);
        $result = $builder->dynamic(bar: 'foo');

        $this->assertEquals($builder, $result);
    }

    public function testNestedWhere()
    {
        $nestedQuery = Mockery::mock(Builder::class);
        $nestedRawQuery = $this->getMockQueryBuilder();
        $nestedQuery->expects('getQuery')->andReturn($nestedRawQuery);
        $nestedQuery->expects('getEagerLoads')->andReturn([]);
        $nestedQuery->expects('removedScopes')->andReturn([]);
        $model = $this->getMockModel()->makePartial();
        $model->expects('newQueryWithoutRelationships')->andReturn($nestedQuery);
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->setModel($model);
        $builder->getQuery()->expects('addNestedWhereQuery')->with($nestedRawQuery, 'and');
        $nestedQuery->expects('foo');

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

    public function testSimpleWhereNot()
    {
        $model = new EloquentBuilderTestStub();
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->whereNot('name', 'foo')->whereNot('name', '<>', 'bar');
        $this->assertSame('select * from "table" where not "name" = ? and not "name" <> ?', $query->toSql());
        $this->assertEquals(['foo', 'bar'], $query->getBindings());
    }

    public function testWhereNot()
    {
        $nestedQuery = Mockery::mock(Builder::class);
        $nestedRawQuery = $this->getMockQueryBuilder();
        $nestedQuery->expects('getQuery')->andReturn($nestedRawQuery);
        $nestedQuery->expects('getEagerLoads')->andReturn([]);
        $nestedQuery->expects('removedScopes')->andReturn([]);
        $model = $this->getMockModel()->makePartial();
        $model->expects('newQueryWithoutRelationships')->andReturn($nestedQuery);
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->setModel($model);
        $builder->getQuery()->expects('addNestedWhereQuery')->with($nestedRawQuery, 'and not');
        $nestedQuery->expects('foo');

        $result = $builder->whereNot(function ($query) {
            $query->foo();
        });
        $this->assertEquals($builder, $result);
    }

    public function testSimpleOrWhereNot()
    {
        $model = new EloquentBuilderTestStub();
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->orWhereNot('name', 'foo')->orWhereNot('name', '<>', 'bar');
        $this->assertSame('select * from "table" where not "name" = ? or not "name" <> ?', $query->toSql());
        $this->assertEquals(['foo', 'bar'], $query->getBindings());
    }

    public function testOrWhereNot()
    {
        $nestedQuery = Mockery::mock(Builder::class);
        $nestedRawQuery = $this->getMockQueryBuilder();
        $nestedQuery->expects('getQuery')->andReturn($nestedRawQuery);
        $nestedQuery->expects('getEagerLoads')->andReturn([]);
        $nestedQuery->expects('removedScopes')->andReturn([]);
        $model = $this->getMockModel()->makePartial();
        $model->expects('newQueryWithoutRelationships')->andReturn($nestedQuery);
        $builder = $this->getBuilder();
        $builder->getQuery()->shouldReceive('from');
        $builder->setModel($model);
        $builder->getQuery()->expects('addNestedWhereQuery')->with($nestedRawQuery, 'or not');
        $nestedQuery->expects('foo');

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

    public function testRealQueryHigherOrderWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->whereNot->two();
        $this->assertSame('select * from "table" where "one" = ? and not ("two" = ?)', $query->toSql());
    }

    public function testRealQueryChainedHigherOrderWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->whereNot->two()->whereNot->three();
        $this->assertSame('select * from "table" where "one" = ? and not ("two" = ?) and not ("three" = ?)', $query->toSql());
    }

    public function testRealQueryHigherOrderOrWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhereNot->two();
        $this->assertSame('select * from "table" where "one" = ? or not ("two" = ?)', $query->toSql());
    }

    public function testRealQueryChainedHigherOrderOrWhereNotScopes()
    {
        $model = new EloquentBuilderTestHigherOrderWhereScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->one()->orWhereNot->two()->orWhereNot->three();
        $this->assertSame('select * from "table" where "one" = ? or not ("two" = ?) or not ("three" = ?)', $query->toSql());
    }

    public function testSimpleWhere()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('where')->with('foo', '=', 'bar');
        $result = $builder->where('foo', '=', 'bar');
        $this->assertEquals($result, $builder);
    }

    public function testPostgresOperatorsWhere()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('where')->with('foo', '@>', 'bar');
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
        $builder->getQuery()->expects('whereIn')->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', [2], 'and');

        $result = $builder->whereBelongsTo($parent);
        $this->assertEquals($result, $builder);

        $builder = $this->getBuilder();
        $builder->shouldReceive('from')->with('eloquent_builder_test_where_belongs_to_stubs');
        $builder->setModel($related);
        $builder->getQuery()->expects('whereIn')->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', [2], 'and');

        $result = $builder->whereBelongsTo($parent, 'parent');
        $this->assertEquals($result, $builder);

        $parents = new Collection([new EloquentBuilderTestWhereBelongsToStub([
            'id' => 2,
            'parent_id' => 1,
        ]), new EloquentBuilderTestWhereBelongsToStub([
            'id' => 3,
            'parent_id' => 1,
        ])]);

        $builder = $this->getBuilder();
        $builder->shouldReceive('from')->with('eloquent_builder_test_where_belongs_to_stubs');
        $builder->setModel($related);
        $builder->getQuery()->expects('whereIn')->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', [2, 3], 'and');

        $result = $builder->whereBelongsTo($parents);
        $this->assertEquals($result, $builder);

        $builder = $this->getBuilder();
        $builder->shouldReceive('from')->with('eloquent_builder_test_where_belongs_to_stubs');
        $builder->setModel($related);
        $builder->getQuery()->expects('whereIn')->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', [2, 3], 'and');

        $result = $builder->whereBelongsTo($parents, 'parent');
        $this->assertEquals($result, $builder);
    }

    public function testWhereAttachedTo()
    {
        $related = new EloquentBuilderTestModelFarRelatedStub;
        $related->id = 49;
        $related->name = 'test';

        $builder = EloquentBuilderTestModelParentStub::whereAttachedTo($related, 'roles');

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where exists (select * from "eloquent_builder_test_model_far_related_stubs" inner join "user_role" on "eloquent_builder_test_model_far_related_stubs"."id" = "user_role"."related_id" where "eloquent_builder_test_model_parent_stubs"."id" = "user_role"."self_id" and "eloquent_builder_test_model_far_related_stubs"."id" in (49))', $builder->toSql());
    }

    public function testWhereAttachedToCollection()
    {
        $model1 = new EloquentBuilderTestModelParentStub;
        $model1->id = 3;
        $model1->name = 'test3';

        $model2 = new EloquentBuilderTestModelParentStub;
        $model2->id = 4;
        $model2->name = 'test4';

        $builder = EloquentBuilderTestModelFarRelatedStub::whereAttachedTo(new Collection([$model1, $model2]), 'roles');

        $this->assertSame('select * from "eloquent_builder_test_model_far_related_stubs" where exists (select * from "eloquent_builder_test_model_parent_stubs" inner join "user_role" on "eloquent_builder_test_model_parent_stubs"."id" = "user_role"."self_id" where "eloquent_builder_test_model_far_related_stubs"."id" = "user_role"."related_id" and "eloquent_builder_test_model_parent_stubs"."id" in (3, 4))', $builder->toSql());
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

    public function testWithCountSecondRelationWithClosure()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withCount(['address', 'foo' => function ($query) {
            $query->where('active', false);
        }]);

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "address_count", (select count(*) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id" and "active" = ?) as "foo_count" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
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

    public function testWithMinExpression()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withMin('foo', new Expression('price - discount'));

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select min(price - discount) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_min_price_discount" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
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

    public function testWithMax()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withMax('foo', 'price');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select max("eloquent_builder_test_model_close_related_stubs"."price") from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_max_price" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithMaxExpression()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withMax('foo', new Expression('price - discount'));

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select max(price - discount) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_max_price_discount" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWithAvg()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withAvg('foo', 'price');

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select avg("eloquent_builder_test_model_close_related_stubs"."price") from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_avg_price" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
    }

    public function testWitAvgExpression()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withAvg('foo', new Expression('price - discount'));

        $this->assertSame('select "eloquent_builder_test_model_parent_stubs".*, (select avg(price - discount) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_avg_price_discount" from "eloquent_builder_test_model_parent_stubs"', $builder->toSql());
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

    public function testWithAggregateAlias()
    {
        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->withAggregate('foo', new Expression('TIMESTAMPDIFF(SECOND, `created_at`, `updated_at`)'), 'sum');

        $this->assertSame(
            'select "eloquent_builder_test_model_parent_stubs".*, (select sum(TIMESTAMPDIFF(SECOND, `created_at`, `updated_at`)) from "eloquent_builder_test_model_close_related_stubs" where "eloquent_builder_test_model_parent_stubs"."foo_id" = "eloquent_builder_test_model_close_related_stubs"."id") as "foo_sum_timestampdiffsecond_created_at_updated_at" from "eloquent_builder_test_model_parent_stubs"',
            $builder->toSql()
        );
    }

    public function testWithAggregateAndSelfRelationConstrain()
    {
        EloquentBuilderTestStub::resolveRelationUsing('children', function ($model) {
            return $model->hasMany(EloquentBuilderTestStub::class, 'parent_id', 'id')->where('enum_value', new stdClass);
        });

        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $relationHash = $model->children()->getRelationCountHash(false);

        $builder = $model->withCount('children');

        $this->assertSame(vsprintf('select "table".*, (select count(*) from "table" as "%s" where "table"."id" = "%s"."parent_id" and "enum_value" = ?) as "children_count" from "table"', [$relationHash, $relationHash]), $builder->toSql());
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

    public function testHasWithConstraintsWithOrWhereAndSubqueryInRelationFromClause()
    {
        EloquentBuilderTestModelParentStub::resolveRelationUsing('addressAsExpression', function ($model) {
            return $model->address()->fromSub(EloquentBuilderTestModelCloseRelatedStub::query(), 'eloquent_builder_test_model_close_related_stubs');
        });

        $model = new EloquentBuilderTestModelParentStub;

        $builder = $model->where('name', 'larry');
        $builder->whereHas('addressAsExpression', function ($q) {
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

    public function testHasNestedWithMorphTo()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $connection = $this->mockConnectionForModel($model, '');

        $morphToKey = $model->morph()->getMorphType();

        $connection->expects('select')->andReturn([
            [$morphToKey => EloquentBuilderTestModelFarRelatedStub::class],
            [$morphToKey => EloquentBuilderTestModelOtherFarRelatedStub::class],
        ]);

        $builder = $model->orWhereHasMorph('morph', [EloquentBuilderTestModelFarRelatedStub::class], function ($q) {
            $q->has('baz');
        })->orWhereHasMorph('morph', [EloquentBuilderTestModelOtherFarRelatedStub::class], function ($q) {
            $q->has('baz');
        });

        $results = $model->has('morph.baz')->toSql();

        // we need to adjust the expected builder because some parathesis are added,
        // which doesn't impact the behavior of the test.

        $builderSql = $builder->toSql();
        $builderSql = str_replace(')))) or ((', '))) or (', $builderSql);

        $this->assertSame($builderSql, $results);
    }

    public function testHasNestedWithMorphToAndMultipleSubRelations()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $connection = $this->mockConnectionForModel($model, '');

        $morphToKey = $model->morph()->getMorphType();

        $connection->expects('select')->andReturn([
            [$morphToKey => EloquentBuilderTestModelFarRelatedStub::class],
            [$morphToKey => EloquentBuilderTestModelOtherFarRelatedStub::class],
        ]);

        $builder = $model->orWhereHasMorph('morph', [EloquentBuilderTestModelFarRelatedStub::class], function ($q) {
            $q->has('baz.bam');
        })->orWhereHasMorph('morph', [EloquentBuilderTestModelOtherFarRelatedStub::class], function ($q) {
            $q->has('baz.bam');
        });

        $results = $model->has('morph.baz.bam')->toSql();

        // we need to adjust the expected builder because some parathesis are added,
        // which doesn't impact the behavior of the test.

        $builderSql = $builder->toSql();
        $builderSql = str_replace(')))) or ((', '))) or (', $builderSql);

        $this->assertSame($builderSql, $results);
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

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereMorphedToCollection()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $secondRelatedModel->id = 2;

        $builder = $model->whereMorphedTo('morph', new Collection([$firstRelatedModel, $secondRelatedModel]));

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)))', $builder->toSql());
        $this->assertEquals([$firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $secondRelatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereMorphedToCollectionWithDifferentModels()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelFarRelatedStub;
        $secondRelatedModel->id = 2;

        $thirdRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $thirdRelatedModel->id = 3;

        $builder = $model->whereMorphedTo('morph', [$firstRelatedModel, $secondRelatedModel, $thirdRelatedModel]);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)) or ("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $thirdRelatedModel->getKey(), $secondRelatedModel->getMorphClass(), $secondRelatedModel->id], $builder->getBindings());
    }

    public function testWhereMorphedToNull()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $builder = $model->whereMorphedTo('morph', null);
        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "eloquent_builder_test_model_parent_stubs"."morph_type" is null', $builder->toSql());
    }

    public function testWhereNotMorphedTo()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->whereNotMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereNotMorphedToCollection()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $secondRelatedModel->id = 2;

        $builder = $model->whereNotMorphedTo('morph', new Collection([$firstRelatedModel, $secondRelatedModel]));

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)))', $builder->toSql());
        $this->assertEquals([$firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $secondRelatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereNotMorphedToCollectionWithDifferentModels()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelFarRelatedStub;
        $secondRelatedModel->id = 2;

        $thirdRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $thirdRelatedModel->id = 3;

        $builder = $model->whereNotMorphedTo('morph', [$firstRelatedModel, $secondRelatedModel, $thirdRelatedModel]);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)) or ("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $thirdRelatedModel->getKey(), $secondRelatedModel->getMorphClass(), $secondRelatedModel->id], $builder->getBindings());
    }

    public function testOrWhereMorphedTo()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->where('bar', 'baz')->orWhereMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals(['baz', $relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testOrWhereMorphedToCollection()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $secondRelatedModel->id = 2;

        $builder = $model->where('bar', 'baz')->orWhereMorphedTo('morph', new Collection([$firstRelatedModel, $secondRelatedModel]));

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)))', $builder->toSql());
        $this->assertEquals(['baz', $firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $secondRelatedModel->getKey()], $builder->getBindings());
    }

    public function testOrWhereMorphedToCollectionWithDifferentModels()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelFarRelatedStub;
        $secondRelatedModel->id = 2;

        $thirdRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $thirdRelatedModel->id = 3;

        $builder = $model->where('bar', 'baz')->orWhereMorphedTo('morph', [$firstRelatedModel, $secondRelatedModel, $thirdRelatedModel]);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)) or ("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals(['baz', $firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $thirdRelatedModel->getKey(), $secondRelatedModel->getMorphClass(), $secondRelatedModel->id], $builder->getBindings());
    }

    public function testOrWhereMorphedToNull()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $builder = $model->where('bar', 'baz')->orWhereMorphedTo('morph', null);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or "eloquent_builder_test_model_parent_stubs"."morph_type" is null', $builder->toSql());
        $this->assertEquals(['baz'], $builder->getBindings());
    }

    public function testOrWhereNotMorphedTo()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->where('bar', 'baz')->orWhereNotMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals(['baz', $relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testOrWhereNotMorphedToCollection()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $secondRelatedModel->id = 2;

        $builder = $model->where('bar', 'baz')->orWhereNotMorphedTo('morph', new Collection([$firstRelatedModel, $secondRelatedModel]));

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)))', $builder->toSql());
        $this->assertEquals(['baz', $firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $secondRelatedModel->getKey()], $builder->getBindings());
    }

    public function testOrWhereNotMorphedToCollectionWithDifferentModels()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;

        $secondRelatedModel = new EloquentBuilderTestModelFarRelatedStub;
        $secondRelatedModel->id = 2;

        $thirdRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $thirdRelatedModel->id = 3;

        $builder = $model->where('bar', 'baz')->orWhereNotMorphedTo('morph', [$firstRelatedModel, $secondRelatedModel, $thirdRelatedModel]);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)) or ("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals(['baz', $firstRelatedModel->getMorphClass(), $firstRelatedModel->getKey(), $thirdRelatedModel->getKey(), $secondRelatedModel->getMorphClass(), $secondRelatedModel->id], $builder->getBindings());
    }

    public function testWhereMorphedToClass()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $builder = $model->whereMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "eloquent_builder_test_model_parent_stubs"."morph_type" = ?', $builder->toSql());
        $this->assertEquals([EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testWhereNotMorphedToClass()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $builder = $model->whereNotMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not ("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ?)', $builder->toSql());
        $this->assertEquals([EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testOrWhereMorphedToClass()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $builder = $model->where('bar', 'baz')->orWhereMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or "eloquent_builder_test_model_parent_stubs"."morph_type" = ?', $builder->toSql());
        $this->assertEquals(['baz', EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testOrWhereNotMorphedToClass()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $builder = $model->where('bar', 'baz')->orWhereNotMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "bar" = ? or not ("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ?)', $builder->toSql());
        $this->assertEquals(['baz', EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testWhereNotMorphedToWithSQLite()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->whereNotMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not (("eloquent_builder_test_model_parent_stubs"."morph_type" is ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereNotMorphedToClassWithSQLite()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $builder = $model->whereNotMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not ("eloquent_builder_test_model_parent_stubs"."morph_type" is ?)', $builder->toSql());
        $this->assertEquals([EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testWhereNotMorphedToWithMySQL()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'MySql');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->whereNotMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from `eloquent_builder_test_model_parent_stubs` where not ((`eloquent_builder_test_model_parent_stubs`.`morph_type` <=> ? and `eloquent_builder_test_model_parent_stubs`.`morph_id` in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereNotMorphedToClassWithMySQL()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'MySql');

        $builder = $model->whereNotMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from `eloquent_builder_test_model_parent_stubs` where not (`eloquent_builder_test_model_parent_stubs`.`morph_type` <=> ?)', $builder->toSql());
        $this->assertEquals([EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testWhereNotMorphedToWithPostgres()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'Postgres');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->whereNotMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereNotMorphedToClassWithPostgres()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'Postgres');

        $builder = $model->whereNotMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not ("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ?)', $builder->toSql());
        $this->assertEquals([EloquentBuilderTestModelCloseRelatedStub::class], $builder->getBindings());
    }

    public function testWhereNotMorphedToWithSqlServer()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'SqlServer');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;

        $builder = $model->whereNotMorphedTo('morph', $relatedModel);

        $this->assertSame('select * from [eloquent_builder_test_model_parent_stubs] where not ((exists (select [eloquent_builder_test_model_parent_stubs].[morph_type] intersect select ?) and [eloquent_builder_test_model_parent_stubs].[morph_id] in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), $relatedModel->getKey()], $builder->getBindings());
    }

    public function testWhereNotMorphedToClassWithSqlServer()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, 'SqlServer');

        $builder = $model->whereNotMorphedTo('morph', EloquentBuilderTestModelCloseRelatedStub::class);

        $this->assertSame('select * from [eloquent_builder_test_model_parent_stubs] where not (exists (select [eloquent_builder_test_model_parent_stubs].[morph_type] intersect select ?))', $builder->toSql());
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

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where "eloquent_builder_test_model_parent_stubs"."morph_type" = ?', $builder->toSql());
        $this->assertEquals(['alias'], $builder->getBindings());

        Relation::morphMap([], false);
    }

    public function testWhereKeyMethodWithInt()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $int = 1;

        $model->expects('getKeyType')->andReturn('int');
        $builder->getQuery()->expects('where')->with($keyName, '=', $int);

        $builder->whereKey($int);
    }

    public function testWhereKeyMethodWithStringZero()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $int = 0;

        $builder->getQuery()->expects('where')->with($keyName, '=', (string) $int);

        $builder->whereKey($int);
    }

    public function testWhereKeyMethodWithStringNull()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('where')->with($keyName, '=', Mockery::on(function ($argument) {
            return $argument === null;
        }));

        $builder->whereKey(null);
    }

    public function testWhereKeyMethodWithArray()
    {
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $array = [1, 2, 3];

        $builder->getQuery()->expects('whereIntegerInRaw')->with($keyName, $array);

        $builder->whereKey($array);
    }

    public function testWhereKeyMethodWithCollection()
    {
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $collection = new Collection([1, 2, 3]);

        $builder->getQuery()->expects('whereIntegerInRaw')->with($keyName, $collection);

        $builder->whereKey($collection);
    }

    public function testWhereKeyMethodWithModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('where')->with($keyName, '=', Mockery::on(function ($argument) {
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

        $builder->getQuery()->expects('where')->with($keyName, '!=', (string) $int);

        $builder->whereKeyNot($int);
    }

    public function testWhereKeyNotMethodWithStringNull()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('where')->with($keyName, '!=', Mockery::on(function ($argument) {
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

        $model->expects('getKeyType')->andReturn('int');
        $builder->getQuery()->expects('where')->with($keyName, '!=', $int);

        $builder->whereKeyNot($int);
    }

    public function testWhereKeyNotMethodWithArray()
    {
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $array = [1, 2, 3];

        $builder->getQuery()->expects('whereIntegerNotInRaw')->with($keyName, $array);

        $builder->whereKeyNot($array);
    }

    public function testWhereKeyNotMethodWithCollection()
    {
        $model = $this->getMockModel();
        $model->expects('getKeyType')->andReturn('int');
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $collection = new Collection([1, 2, 3]);

        $builder->getQuery()->expects('whereIntegerNotInRaw')->with($keyName, $collection);

        $builder->whereKeyNot($collection);
    }

    public function testWhereKeyNotMethodWithModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('where')->with($keyName, '!=', Mockery::on(function ($argument) {
            return $argument === '1';
        }));

        $builder->whereKeyNot(new class extends Model
        {
            protected $attributes = ['id' => 1];
        });
    }

    public function testOrWhereKeyMethodWithInt()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereKey(1)->orWhereKey(2);

        $this->assertSame('select * from "table" where "table"."id" = ? or ("table"."id" = ?)', $query->toSql());
        $this->assertEquals([1, 2], $query->getBindings());
    }

    public function testOrWhereKeyMethodWithArray()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereKey(1)->orWhereKey([2, 3]);

        $this->assertSame('select * from "table" where "table"."id" = ? or ("table"."id" in (2, 3))', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    public function testOrWhereKeyMethodWithCollection()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereKey(1)->orWhereKey(new Collection([2, 3]));

        $this->assertSame('select * from "table" where "table"."id" = ? or ("table"."id" in (2, 3))', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    public function testOrWhereKeyNotMethodWithInt()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereKey(1)->orWhereKeyNot(2);

        $this->assertSame('select * from "table" where "table"."id" = ? or ("table"."id" != ?)', $query->toSql());
        $this->assertEquals([1, 2], $query->getBindings());
    }

    public function testOrWhereKeyNotMethodWithArray()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereKey(1)->orWhereKeyNot([2, 3]);

        $this->assertSame('select * from "table" where "table"."id" = ? or ("table"."id" not in (2, 3))', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    public function testOrWhereKeyNotMethodWithCollection()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereKey(1)->orWhereKeyNot(new Collection([2, 3]));

        $this->assertSame('select * from "table" where "table"."id" = ? or ("table"."id" not in (2, 3))', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    public function testOrWhereKeyMethodsHonorWhereKeyOverrides()
    {
        $model = new EloquentBuilderTestWhereKeyOverrideStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereKey(1)->orWhereKey(2)->orWhereKeyNot(3);

        $this->assertSame('select * from "table" where ("tenant_id" = ? and "local_id" = ?) or (("tenant_id" = ? and "local_id" = ?)) or (not ("tenant_id" = ? and "local_id" = ?))', $query->toSql());
        $this->assertEquals([1, 1, 2, 2, 3, 3], $query->getBindings());
    }

    public function testExceptMethodWithModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('where')->with($keyName, '!=', Mockery::on(function ($argument) {
            return $argument === '1';
        }));

        $builder->except(new class extends Model
        {
            protected $attributes = ['id' => 1];
        });
    }

    public function testExceptMethodWithCollectionOfModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('whereNotIn')->with($keyName, Mockery::on(function ($argument) {
            return $argument === [1, 2];
        }));

        $models = new Collection([
            new class extends Model
            {
                protected $attributes = ['id' => 1];
            },
            new class extends Model
            {
                protected $attributes = ['id' => 2];
            },
        ]);

        $builder->except($models);
    }

    public function testExceptMethodWithArrayOfModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('whereNotIn')->with($keyName, Mockery::on(function ($argument) {
            return $argument === [1, 2];
        }));

        $models = [
            new class extends Model
            {
                protected $attributes = ['id' => 1];
            },
            new class extends Model
            {
                protected $attributes = ['id' => 2];
            },
        ];

        $builder->except($models);
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
        $model->expects('getCreatedAtColumn')->andReturn('foo');
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('latest')->with('foo');

        $builder->latest();
    }

    public function testLatestWithoutColumnWithoutCreatedAt()
    {
        $model = $this->getMockModel();
        $model->expects('getCreatedAtColumn')->andReturn(null);
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('latest')->with('created_at');

        $builder->latest();
    }

    public function testLatestWithColumn()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('latest')->with('foo');

        $builder->latest('foo');
    }

    public function testOldestWithoutColumnWithCreatedAt()
    {
        $model = $this->getMockModel();
        $model->expects('getCreatedAtColumn')->andReturn('foo');
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('oldest')->with('foo');

        $builder->oldest();
    }

    public function testOldestWithoutColumnWithoutCreatedAt()
    {
        $model = $this->getMockModel();
        $model->expects('getCreatedAtColumn')->andReturn(null);
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('oldest')->with('created_at');

        $builder->oldest();
    }

    public function testOldestWithColumn()
    {
        $model = $this->getMockModel();
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('oldest')->with('foo');

        $builder->oldest('foo');
    }

    public function testUpdate()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')
            ->with('update "table" set "foo" = ?, "table"."updated_at" = ?', ['bar', $now])->andReturn(1);

        $result = $builder->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithTimestampValue()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getTablePrefix')->times(2)->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')
            ->with('update "table" set "foo" = ?, "table"."updated_at" = ?', ['bar', null])->andReturn(1);

        $result = $builder->update(['foo' => 'bar', 'updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithQualifiedTimestampValue()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')
            ->with('update "table" set "table"."foo" = ?, "table"."updated_at" = ?', ['bar', null])->andReturn(1);

        $result = $builder->update(['table.foo' => 'bar', 'table.updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithoutTimestamp()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getTablePrefix')->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubWithoutTimestamp;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')
            ->with('update "table" set "foo" = ?', ['bar'])->andReturn(1);

        $result = $builder->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithAlias()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')
            ->with('update "table" as "alias" set "foo" = ?, "alias"."updated_at" = ?', ['bar', $now])->andReturn(1);

        $result = $builder->from('table as alias')->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithAliasWithQualifiedTimestampValue()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')
            ->with('update "table" as "alias" set "foo" = ?, "alias"."updated_at" = ?', ['bar', null])->andReturn(1);

        $result = $builder->from('table as alias')->update(['foo' => 'bar', 'alias.updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpsert()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->andReturn('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('upsert')
            ->with([
                ['email' => 'foo', 'name' => 'bar', 'updated_at' => $now, 'created_at' => $now],
                ['name' => 'bar2', 'email' => 'foo2', 'updated_at' => $now, 'created_at' => $now],
            ], ['email'], ['email', 'name', 'updated_at'])->andReturn(2);

        $result = $builder->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], ['email']);

        $this->assertEquals(2, $result);
    }

    public function testTouch()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->andReturn('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('update')->with(['updated_at' => $now])->andReturn(2);

        $result = $builder->touch();

        $this->assertEquals(2, $result);
    }

    public function testTouchWithCustomColumn()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->andReturn('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('update')->with(['published_at' => $now])->andReturn(2);

        $result = $builder->touch('published_at');

        $this->assertEquals(2, $result);
    }

    public function testTouchWithMultipleColumns()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->andReturn('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('update')->with(['published_at' => $now, 'verified_at' => $now])->andReturn(2);

        $result = $builder->touch(['published_at', 'verified_at']);

        $this->assertEquals(2, $result);
    }

    public function testTouchWithoutUpdatedAtColumn()
    {
        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('table')->andReturn('table');
        $query->from = 'table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubWithoutTimestamp;
        $builder->setModel($model);

        $query->shouldNotReceive('update');

        $result = $builder->touch();

        $this->assertFalse($result);
    }

    public function testWithCastsMethod()
    {
        $builder = new Builder($this->getMockQueryBuilder());
        $model = $this->getMockModel();
        $builder->setModel($model);

        $model->expects('mergeCasts')->with(['foo' => 'bar']);
        $builder->withCasts(['foo' => 'bar']);
    }

    public function testClone()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getTablePrefix')->times(2)->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = new Builder($query);
        $builder->select('*')->from('users');
        $clone = $builder->clone()->where('email', 'foo');

        $this->assertNotSame($builder, $clone);
        $this->assertSame('select * from "users"', $builder->toSql());
        $this->assertSame('select * from "users" where "email" = ?', $clone->toSql());
    }

    public function testCloneModelMakesAFreshCopyOfTheModel()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getTablePrefix')->times(2)->andReturn('');
        $query = new BaseBuilder($connection, new Grammar($connection), Mockery::mock(Processor::class));
        $builder = (new Builder($query))->setModel(new EloquentBuilderTestStub);
        $builder->select('*')->from('users');

        $onCloneCallbackCalledCount = 0;

        $onCloneQuery = null;

        $builder->onClone(function (Builder $query) use (&$onCloneCallbackCalledCount, &$onCloneQuery) {
            $onCloneCallbackCalledCount++;

            $onCloneQuery = $query;
        });

        $clone = $builder->clone()->where('email', 'foo');

        $this->assertNotSame($builder, $clone);
        $this->assertSame('select * from "users"', $builder->toSql());
        $this->assertSame('select * from "users" where "email" = ?', $clone->toSql());

        $this->assertSame(1, $onCloneCallbackCalledCount);
        $this->assertSame($onCloneQuery, $clone);
    }

    public function testToRawSql()
    {
        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('toRawSql')
            ->andReturn('select * from "users" where "email" = \'foo\'');

        $builder = new Builder($query);

        $this->assertSame('select * from "users" where "email" = \'foo\'', $builder->toRawSql());
    }

    public function testPassthruMethodsCallsAreNotCaseSensitive()
    {
        $query = Mockery::mock(BaseBuilder::class);

        $mockResponse = 'select 1';
        $query
            ->expects('toRawSql')
            ->andReturn($mockResponse)
            ->times(3);

        $builder = new Builder($query);

        $this->assertSame('select 1', $builder->TORAWSQL());
        $this->assertSame('select 1', $builder->toRawSql());
        $this->assertSame('select 1', $builder->toRawSQL());
    }

    public function testPassthruArrayElementsMustAllBeLowercase()
    {
        $builder = new class(Mockery::mock(BaseBuilder::class)) extends Builder
        {
            // expose protected member for test
            public function getPassthru(): array
            {
                return $this->passthru;
            }
        };

        $passthru = $builder->getPassthru();

        foreach ($passthru as $method) {
            $lowercaseMethod = strtolower($method);

            $this->assertSame(
                $lowercaseMethod,
                $method,
                'Eloquent\\Builder relies on lowercase method names in $passthru array to correctly mimic PHP case-insensitivity on method dispatch.'.
                    'If you are adding a new method to the $passthru array, make sure the name is lowercased.'
            );
        }
    }

    public function testPipeCallback()
    {
        $query = new Builder(new BaseBuilder(
            $connection = new Connection(new PDO('sqlite::memory:')),
            new Grammar($connection),
            new Processor,
        ));

        $result = $query->pipe(fn (Builder $query) => 5);
        $this->assertSame(5, $result);

        $result = $query->pipe(fn (Builder $query) => null);
        $this->assertSame($query, $result);

        $result = $query->pipe(function (Builder $query) {
            //
        });
        $this->assertSame($query, $result);

        $this->assertCount(0, $query->getQuery()->wheres);
        $result = $query->pipe(fn (Builder $query) => $query->where('foo', 'bar'));
        $this->assertSame($query, $result);
        $this->assertCount(1, $query->getQuery()->wheres);
    }

    protected function mockConnectionForModel($model, $database)
    {
        $grammarClass = 'Illuminate\Database\Query\Grammars\\'.$database.'Grammar';
        $processorClass = 'Illuminate\Database\Query\Processors\\'.$database.'Processor';
        $processor = new $processorClass;
        $connection = Mockery::mock(Connection::class, ['getPostProcessor' => $processor]);
        $grammar = new $grammarClass($connection);
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new BaseBuilder($connection, $grammar, $processor);
        });
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $resolver = Mockery::mock(ConnectionResolverInterface::class, ['connection' => $connection]);
        $class = get_class($model);
        $class::setConnectionResolver($resolver);

        return $connection;
    }

    protected function getBuilder()
    {
        return new Builder($this->getMockQueryBuilder());
    }

    public function testIncrementEachCallsToBaseWithUpdatedAt()
    {
        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('foo_table');
        $query->from = 'foo_table';
        $query->expects('incrementEach')->withArgs(function ($columns, $extra) {
            return $columns === ['votes' => 5] && array_key_exists('foo_table.updated_at', $extra);
        })->andReturn(1);

        $builder = new Builder($query);
        $model = $this->getMockModel();
        $model->expects('usesTimestamps')->andReturn(true);
        $model->expects('getUpdatedAtColumn')->times(2)->andReturn('updated_at');
        $model->expects('freshTimestampString')->andReturn('2026-03-26 00:00:00');
        $model->expects('hasSetMutator')->andReturn(false);
        $model->expects('hasAttributeSetMutator')->andReturn(false);
        $model->expects('hasCast')->andReturn(false);
        $builder->setModel($model);

        $result = $builder->incrementEach(['votes' => 5]);
        $this->assertEquals(1, $result);
    }

    public function testDecrementEachCallsToBaseWithUpdatedAt()
    {
        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('foo_table');
        $query->from = 'foo_table';
        $query->expects('decrementEach')->withArgs(function ($columns, $extra) {
            return $columns === ['votes' => 3] && array_key_exists('foo_table.updated_at', $extra);
        })->andReturn(1);

        $builder = new Builder($query);
        $model = $this->getMockModel();
        $model->expects('usesTimestamps')->andReturn(true);
        $model->expects('getUpdatedAtColumn')->times(2)->andReturn('updated_at');
        $model->expects('freshTimestampString')->andReturn('2026-03-26 00:00:00');
        $model->expects('hasSetMutator')->andReturn(false);
        $model->expects('hasAttributeSetMutator')->andReturn(false);
        $model->expects('hasCast')->andReturn(false);
        $builder->setModel($model);

        $result = $builder->decrementEach(['votes' => 3]);
        $this->assertEquals(1, $result);
    }

    public function testIncrementEachWithoutTimestamps()
    {
        $query = Mockery::mock(BaseBuilder::class);
        $query->expects('from')->with('foo_table');
        $query->expects('incrementEach')->with(['votes' => 1], [])->andReturn(1);

        $builder = new Builder($query);
        $model = $this->getMockModel();
        $model->expects('usesTimestamps')->andReturn(false);
        $builder->setModel($model);

        $result = $builder->incrementEach(['votes' => 1]);
        $this->assertEquals(1, $result);
    }

    protected function getMockModel()
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getKeyName')->andReturn('foo');
        $model->shouldReceive('getTable')->andReturn('foo_table');
        $model->shouldReceive('getQualifiedKeyName')->andReturn('foo_table.foo');

        return $model;
    }

    protected function getMockQueryBuilder()
    {
        $query = Mockery::mock(BaseBuilder::class);
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

class EloquentBuilderTestDynamicScopeStub extends Model
{
    public function scopeDynamic($query, $foo = 'foo', $bar = 'bar')
    {
        $query->where($foo, $bar);
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

    public function bam()
    {
        return $this->hasMany(EloquentBuilderTestModelOtherFarRelatedStub::class);
    }
}

class EloquentBuilderTestModelFarRelatedStub extends Model
{
    public function roles()
    {
        return $this->belongsToMany(
            EloquentBuilderTestModelParentStub::class,
            'user_role',
            'related_id',
            'self_id',
        );
    }

    public function baz()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class);
    }
}

class EloquentBuilderTestModelOtherFarRelatedStub extends Model
{
    public function roles()
    {
        return $this->belongsToMany(
            EloquentBuilderTestModelParentStub::class,
            'user_role',
            'related_id',
            'self_id',
        );
    }

    public function baz()
    {
        return $this->belongsTo(EloquentBuilderTestModelCloseRelatedStub::class);
    }
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

class EloquentBuilderTestWhereKeyOverrideStub extends Model
{
    protected $table = 'table';

    public function newEloquentBuilder($query)
    {
        return new EloquentBuilderTestWhereKeyOverrideBuilder($query);
    }
}

class EloquentBuilderTestWhereKeyOverrideBuilder extends Builder
{
    public function whereKey($id)
    {
        return $this->where(fn ($query) => $query->where('tenant_id', '=', $id)->where('local_id', '=', $id));
    }

    public function whereKeyNot($id)
    {
        return $this->whereNot(fn ($query) => $query->where('tenant_id', '=', $id)->where('local_id', '=', $id));
    }
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

enum EloquentBuilderTestBackedEnum: string
{
    case Bar = 'bar';
}

enum EloquentBuilderTestUnitEnum
{
    case Baz;
}
