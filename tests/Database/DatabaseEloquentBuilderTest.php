<?php

namespace Illuminate\Tests\Database;

use BadMethodCallException;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsBinary;
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
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Tests\Database\Concerns\RestoresConnectionResolver;
use Illuminate\Tests\Database\Fixtures\Enums\Bar;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseEloquentBuilderTest extends TestCase
{
    use RestoresConnectionResolver;
    use VerifiesDoubles;

    protected function tearDown(): void
    {
        Model::clearBootedModels();
    }

    protected function setUp(): void
    {
        $this->useInMemoryConnection();
    }

    public function testFindMethod()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $builder->setModel($model);
        $model->expects('getKeyType')->returns('int');
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar', 'and');
        $builder->expects('first')->with(['column'])->returns('baz');

        $result = $builder->find('bar', ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindSoleMethod()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $builder->setModel($model);
        $model->expects('getKeyType')->returns('int');
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar', 'and');
        $builder->expects('sole')->with(['column'])->returns('baz');

        $result = $builder->findSole('bar', ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindManyMethod()
    {
        // ids are not empty
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $model->expects('getKeyType')->returns('int');
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', ['one', 'two']);
        $builder->expects('get')->with(['column'])->returns(['baz']);

        $result = $builder->findMany(['one', 'two'], ['column']);
        $this->assertEquals(['baz'], $result);

        // ids are empty array
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $model->expects('newCollection')->with(Argument::none())->returns('emptycollection');
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIntegerInRaw')->never();
        $builder->expects('get')->never();

        $result = $builder->findMany([], ['column']);
        $this->assertSame('emptycollection', $result);

        // ids are empty collection
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $model->expects('newCollection')->with(Argument::none())->returns('emptycollection');
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIn')->never();
        $builder->expects('get')->never();

        $result = $builder->findMany(collect(), ['column']);
        $this->assertSame('emptycollection', $result);
    }

    public function testFindOrFailMethodThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $model->expects('getKeyType')->returns('int');
        $builder->setModel($model);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar', 'and');
        $builder->expects('first')->with(['column'])->returns(null);
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
        $model->expects('getKey')->returns(1);
        $model->expects('getKeyType')->returns('int');

        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->expects('get')->with(['column'])->returns(new Collection([$model]));
        $builder->findOrFail([1, 2], ['column']);
    }

    public function testFindOrFailMethodWithManyUsingCollectionThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $model = $this->getMockModel();
        $model->expects('getKey')->returns(1);
        $model->expects('getKeyType')->returns('int');

        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $builder->setModel($model);
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->expects('get')->with(['column'])->returns(new Collection([$model]));
        $builder->findOrFail(new Collection([1, 2]), ['column']);
    }

    #[DataProvider('enumIdsProvider')]
    public function testFindOrFailWithEnumIds($id, $value, $useCollection)
    {
        $model = new EloquentBuilderTestStub;
        $model->setAttribute($model->getKeyName(), $value);

        $builder = Double::for(Builder::class)->passthru(new Builder($model->getConnection()->query()));
        $builder->setModel($model);
        $models = new Collection([$model]);
        $ids = [$id, $id, $value];
        $ids = $useCollection ? new BaseCollection($ids) : $ids;
        $builder->expects('find')->with($ids, ['column'])->returns($models);

        $this->assertSame($models, $builder->findOrFail($ids, ['column']));
    }

    #[DataProvider('enumIdsProvider')]
    public function testFindOrFailWithMissingEnumIds($id, $value, $useCollection)
    {
        $model = new EloquentBuilderTestStub;
        $model->setKeyType('string');
        $model->setAttribute($model->getKeyName(), 'existing');

        $builder = Double::for(Builder::class)->passthru(new Builder($model->getConnection()->query()));
        $builder->setModel($model);
        $ids = ['existing', $id];
        $ids = $useCollection ? new BaseCollection($ids) : $ids;
        $builder->expects('find')->with($ids, ['*'])->returns(new Collection([$model]));

        try {
            $builder->findOrFail($ids);
            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(EloquentBuilderTestStub::class, $exception->getModel());
            $this->assertSame([$value], array_values($exception->getIds()));
        }
    }

    public static function enumIdsProvider()
    {
        foreach ([false, true] as $useCollection) {
            yield [Bar::FOO, 5, $useCollection];
            yield [EloquentBuilderTestBackedEnum::Bar, 'bar', $useCollection];
        }
    }

    public function testFirstOrFailMethodThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $builder->setModel($this->getMockModel());
        $builder->expects('first')->with(['column'])->returns(null);
        $builder->firstOrFail(['column']);
    }

    public function testFindWithMany()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $model->expects('getKeyType')->returns('int');
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->setModel($model);
        $builder->expects('get')->with(['column'])->returns('baz');

        $result = $builder->find([1, 2], ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFindWithManyUsingCollection()
    {
        $ids = collect([1, 2]);
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $model->expects('getKeyType')->returns('int');
        $builder->getQuery()->expects('whereIntegerInRaw')->with('foo_table.foo', [1, 2]);
        $builder->setModel($model);
        $builder->expects('get')->with(['column'])->returns('baz');

        $result = $builder->find($ids, ['column']);
        $this->assertSame('baz', $result);
    }

    public function testFirstMethod()
    {
        $connection = $this->newConnection();
        $builder = $this->newBuilder($connection);

        $result = $builder->first();

        $this->assertSame('taylor', $result->name);
        $this->assertSame('select * from "table" limit 1', $connection->getQueryLog()[0]['query']);
    }

    public function testQualifyColumn()
    {
        $builder = new Builder(Double::for(BaseBuilder::class));
        $builder->expects('from')->with('foo_table');

        $builder->setModel(new EloquentBuilderTestStubStringPrimaryKey);

        $this->assertSame('foo_table.column', $builder->qualifyColumn('column'));
    }

    public function testQualifyColumns()
    {
        $builder = new Builder(Double::for(BaseBuilder::class));
        $builder->expects('from')->with('foo_table');

        $builder->setModel(new EloquentBuilderTestStubStringPrimaryKey);

        $this->assertEquals(['foo_table.column', 'foo_table.name'], $builder->qualifyColumns(['column', 'name']));
    }

    public function testQualifyColumnWithTableAlias()
    {
        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table');
        $query->from = 'foo_table as alias';

        $builder = new Builder($query);
        $builder->setModel(new EloquentBuilderTestStubStringPrimaryKey);

        $this->assertSame('alias.column', $builder->qualifyColumn('column'));
        $this->assertSame('alias.column', $builder->qualifyColumn('foo_table.column'));
        $this->assertSame('other_table.column', $builder->qualifyColumn('other_table.column'));
        $this->assertEquals(['alias.column', 'alias.name'], $builder->qualifyColumns(['column', 'name']));
    }

    public function testGetMethodLoadsModelsAndHydratesEagerRelations()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $builder->expects('getModels')->with(['foo'])->returns(['bar']);
        $builder->expects('eagerLoadRelations')->with(['bar'])->returns(['bar', 'baz']);
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('newCollection')->with(['bar', 'baz'])->returns(new Collection(['bar', 'baz']));

        $results = $builder->get(['foo']);
        $this->assertEquals(['bar', 'baz'], $results->all());
    }

    public function testGetMethodDoesntHydrateEagerRelationsWhenNoResultsAreReturned()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $builder->expects('getModels')->with(['foo'])->returns([]);
        $builder->expects('eagerLoadRelations')->never();
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('newCollection')->with([])->returns(new Collection([]));

        $results = $builder->get(['foo']);
        $this->assertSame([], $results->all());
    }

    public function testValueMethodWithModelFound()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $mockModel = new stdClass;
        $mockModel->name = 'foo';
        $builder->expects('first')->with(['name'])->returns($mockModel);

        $this->assertSame('foo', $builder->value('name'));
    }

    public function testValueMethodWithModelNotFound()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $builder->expects('first')->with(['name'])->returns(null);

        $this->assertNull($builder->value('name'));
    }

    public function testValueOrFailMethodWithModelFound()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $mockModel = new stdClass;
        $mockModel->name = 'foo';
        $builder->expects('first')->with(['name'])->returns($mockModel);

        $this->assertSame('foo', $builder->valueOrFail('name'));
    }

    public function testValueOrFailMethodWithModelNotFoundThrowsModelNotFoundException()
    {
        $this->expectException(ModelNotFoundException::class);

        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $model = $this->getMockModel();
        $model->expects('getKeyType')->returns('int');
        $builder->setModel($model);
        $builder->getQuery()->expects('where')->with('foo_table.foo', '=', 'bar', 'and');
        $builder->expects('first')->with(['column'])->returns(null);
        $builder->whereKey('bar')->valueOrFail('column');
    }

    public function testChunkWithLastChunkComplete()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 3, 4]);

        $chunks = [];
        $builder->orderBy('id')->chunk(2, function ($results) use (&$chunks) {
            $chunks[] = $results->pluck('id')->all();
        });

        $this->assertSame([[1, 2], [3, 4]], $chunks);
        $this->assertSame([
            'select * from "table" order by "id" asc limit 2 offset 0',
            'select * from "table" order by "id" asc limit 2 offset 2',
            'select * from "table" order by "id" asc limit 2 offset 4',
        ], array_column($connection->getQueryLog(), 'query'));
    }

    public function testChunkWithLastChunkPartial()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 3]);

        $chunks = [];
        $builder->orderBy('id')->chunk(2, function ($results) use (&$chunks) {
            $chunks[] = $results->pluck('id')->all();
        });

        $this->assertSame([[1, 2], [3]], $chunks);
        $this->assertSame([
            'select * from "table" order by "id" asc limit 2 offset 0',
            'select * from "table" order by "id" asc limit 2 offset 2',
        ], array_column($connection->getQueryLog(), 'query'));
    }

    public function testChunkCanBeStoppedByReturningFalse()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 3]);

        $chunks = [];
        $builder->orderBy('id')->chunk(2, function ($results) use (&$chunks) {
            $chunks[] = $results->pluck('id')->all();

            return false;
        });

        $this->assertSame([[1, 2]], $chunks);
        $this->assertSame([
            'select * from "table" order by "id" asc limit 2 offset 0',
        ], array_column($connection->getQueryLog(), 'query'));
    }

    public function testChunkWithCountZero()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 3]);

        $builder->orderBy('id')->chunk(0, function () {
            $this->fail('Should not be called.');
        });

        $this->assertSame([], $connection->getQueryLog());
    }

    public function testChunkPaginatesUsingIdWithLastChunkComplete()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 10, 11], 'someIdField');

        $chunks = [];
        $builder->chunkById(2, function ($results) use (&$chunks) {
            $chunks[] = $results->pluck('someIdField')->all();
        }, 'someIdField');

        $this->assertSame([[1, 2], [10, 11]], $chunks);
        $this->assertSame([[], [2], [11]], $this->pagedBindings($connection));
        $this->assertSame(['select * from "table" where "someIdField" is not null order by "someIdField" asc limit 2', 'select * from "table" where "someIdField" > ? order by "someIdField" asc limit 2', 'select * from "table" where "someIdField" > ? order by "someIdField" asc limit 2'], array_column($connection->getQueryLog(), 'query'));
    }

    public function testChunkPaginatesUsingIdWithLastChunkPartial()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 10], 'someIdField');

        $chunks = [];
        $builder->chunkById(2, function ($results) use (&$chunks) {
            $chunks[] = $results->pluck('someIdField')->all();
        }, 'someIdField');

        $this->assertSame([[1, 2], [10]], $chunks);
        $this->assertSame([[], [2]], $this->pagedBindings($connection));
        $this->assertSame(['select * from "table" where "someIdField" is not null order by "someIdField" asc limit 2', 'select * from "table" where "someIdField" > ? order by "someIdField" asc limit 2'], array_column($connection->getQueryLog(), 'query'));
    }

    public function testChunkPaginatesUsingIdWithCountZero()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 10], 'someIdField');

        $builder->chunkById(0, function () {
            $this->fail('Should never be called.');
        }, 'someIdField');

        $this->assertSame([], $connection->getQueryLog());
    }

    public function testLazyWithLastChunkComplete()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 3, 4]);

        $this->assertSame([1, 2, 3, 4], $builder->orderBy('id')->lazy(2)->map(fn ($model) => $model->id)->all());
        $this->assertSame([
            'select * from "table" order by "id" asc limit 2 offset 0',
            'select * from "table" order by "id" asc limit 2 offset 2',
            'select * from "table" order by "id" asc limit 2 offset 4',
        ], array_column($connection->getQueryLog(), 'query'));
    }

    public function testLazyWithLastChunkPartial()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 3]);

        $this->assertSame([1, 2, 3], $builder->orderBy('id')->lazy(2)->map(fn ($model) => $model->id)->all());
        $this->assertSame([
            'select * from "table" order by "id" asc limit 2 offset 0',
            'select * from "table" order by "id" asc limit 2 offset 2',
        ], array_column($connection->getQueryLog(), 'query'));
    }

    public function testLazyIsLazy()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 3, 4]);

        $this->assertSame([1, 2], $builder->orderBy('id')->lazy(2)->take(2)->map(fn ($model) => $model->id)->all());
        $this->assertSame([
            'select * from "table" order by "id" asc limit 2 offset 0',
        ], array_column($connection->getQueryLog(), 'query'));
    }

    public function testLazyByIdWithLastChunkComplete()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 10, 11], 'someIdField');

        $this->assertSame([1, 2, 10, 11], $builder->lazyById(2, 'someIdField')->map(fn ($model) => $model->someIdField)->all());
        $this->assertSame([[], [2], [11]], $this->pagedBindings($connection));
    }

    public function testLazyByIdWithLastChunkPartial()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 10], 'someIdField');

        $this->assertSame([1, 2, 10], $builder->lazyById(2, 'someIdField')->map(fn ($model) => $model->someIdField)->all());
        $this->assertSame([[], [2]], $this->pagedBindings($connection));
    }

    public function testLazyByIdIsLazy()
    {
        [$connection, $builder] = $this->newPagedBuilder([1, 2, 10, 11], 'someIdField');

        $this->assertSame([1, 2], $builder->lazyById(2, 'someIdField')->take(2)->map(fn ($model) => $model->someIdField)->all());
        $this->assertSame([[]], $this->pagedBindings($connection));
    }

    public function testChunkByIdUsesTheRawKeyOfCastModels()
    {
        [$connection, $builder, $ids] = $this->newBinaryUuidPagedBuilder();

        $chunks = [];
        $builder->chunkById(2, function ($results) use (&$chunks) {
            $chunks[] = $results->pluck('id')->all();

            return count($chunks) < 3;
        });

        $this->assertSame([[$ids[0], $ids[1]], [$ids[2]]], $chunks);
        $this->assertSame([[], [hex2bin(str_replace('-', '', $ids[1]))]], $this->pagedBindings($connection));
    }

    public function testLazyByIdUsesTheRawKeyOfCastModels()
    {
        [$connection, $builder, $ids] = $this->newBinaryUuidPagedBuilder();

        $this->assertSame($ids, $builder->lazyById(2)->take(4)->map(fn ($model) => $model->id)->all());
        $this->assertSame([[], [hex2bin(str_replace('-', '', $ids[1]))]], $this->pagedBindings($connection));
    }

    public function testPluckReturnsTheMutatedAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with('name', null)->returns(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->returns(true);
        $builder->getModel()->expects('newFromBuilder')->times(2)->resolves(fn ($attributes) => new EloquentBuilderTestPluckStub($attributes));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck('name')->all());
    }

    public function testPluckReturnsTheCastedAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with('name', null)->returns(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->returns(false);
        $builder->getModel()->expects('hasCast')->with('name')->returns(true);
        $builder->getModel()->expects('newFromBuilder')->times(2)->resolves(fn ($attributes) => new EloquentBuilderTestPluckStub($attributes));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck('name')->all());
    }

    public function testPluckReturnsTheDateAttributesOfAModel()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with('created_at', null)->returns(new BaseCollection(['2010-01-01 00:00:00', '2011-01-01 00:00:00']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('created_at')->returns(false);
        $builder->getModel()->expects('hasCast')->with('created_at')->returns(false);
        $builder->getModel()->expects('getDates')->returns(['created_at']);
        $builder->getModel()->expects('newFromBuilder')->times(2)->resolves(fn ($attributes) => new EloquentBuilderTestPluckDatesStub($attributes));

        $this->assertEquals(['date_2010-01-01 00:00:00', 'date_2011-01-01 00:00:00'], $builder->pluck('created_at')->all());
    }

    public function testQualifiedPluckReturnsTheMutatedAttributesOfAModel()
    {
        $model = $this->getMockModel();
        $model->expects('qualifyColumn')->times(2)->with('name')->returns('foo_table.name');

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with($model->qualifyColumn('name'), null)->returns(new BaseCollection(['bar', 'baz']));
        $builder->setModel($model);
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->returns(true);
        $builder->getModel()->expects('newFromBuilder')->times(2)->resolves(fn ($attributes) => new EloquentBuilderTestPluckStub($attributes));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck($model->qualifyColumn('name'))->all());
    }

    public function testQualifiedPluckReturnsTheCastedAttributesOfAModel()
    {
        $model = $this->getMockModel();
        $model->expects('qualifyColumn')->times(2)->with('name')->returns('foo_table.name');

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with($model->qualifyColumn('name'), null)->returns(new BaseCollection(['bar', 'baz']));
        $builder->setModel($model);
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->returns(false);
        $builder->getModel()->expects('hasCast')->with('name')->returns(true);
        $builder->getModel()->expects('newFromBuilder')->times(2)->resolves(fn ($attributes) => new EloquentBuilderTestPluckStub($attributes));

        $this->assertEquals(['foo_bar', 'foo_baz'], $builder->pluck($model->qualifyColumn('name'))->all());
    }

    public function testQualifiedPluckReturnsTheDateAttributesOfAModel()
    {
        $model = $this->getMockModel();
        $model->expects('qualifyColumn')->times(2)->with('created_at')->returns('foo_table.created_at');

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with($model->qualifyColumn('created_at'), null)->returns(new BaseCollection(['2010-01-01 00:00:00', '2011-01-01 00:00:00']));
        $builder->setModel($model);
        $builder->getModel()->expects('hasAnyGetMutator')->with('created_at')->returns(false);
        $builder->getModel()->expects('hasCast')->with('created_at')->returns(false);
        $builder->getModel()->expects('getDates')->returns(['created_at']);
        $builder->getModel()->expects('newFromBuilder')->times(2)->resolves(fn ($attributes) => new EloquentBuilderTestPluckDatesStub($attributes));

        $this->assertEquals(['date_2010-01-01 00:00:00', 'date_2011-01-01 00:00:00'], $builder->pluck($model->qualifyColumn('created_at'))->all());
    }

    public function testPluckWithoutModelGetterJustReturnsTheAttributesFoundInDatabase()
    {
        $builder = $this->getBuilder();
        $builder->getQuery()->expects('pluck')->with('name', null)->returns(new BaseCollection(['bar', 'baz']));
        $builder->setModel($this->getMockModel());
        $builder->getModel()->expects('hasAnyGetMutator')->with('name')->returns(false);
        $builder->getModel()->expects('hasCast')->with('name')->returns(false);
        $builder->getModel()->expects('getDates')->returns(['created_at']);

        $this->assertEquals(['bar', 'baz'], $builder->pluck('name')->all());
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
        $builder = $this->newBuilder($this->newConnection());

        $models = $builder->getModels(['name', 'age']);

        $this->assertContainsOnlyInstancesOf(EloquentBuilderTestStub::class, $models);
        $this->assertSame(['taylor', 'dayle'], array_map(fn ($model) => $model->name, $models));
        $this->assertSame(28, $models[1]->age);
    }

    public function testEagerLoadRelationsLoadTopLevelRelationships()
    {
        $builder = new EloquentBuilderEagerLoadSpy($this->getMockQueryBuilder());
        $nop1 = function () {
            //
        };
        $nop2 = function () {
            //
        };
        $builder->setEagerLoads(['foo' => $nop1, 'foo.bar' => $nop2]);

        $results = $builder->eagerLoadRelations(['models']);

        $this->assertEquals(['foo'], $results);
        $this->assertSame([[['models'], 'foo', $nop1]], $builder->eagerLoaded);
    }

    public function testEagerLoadRelationsCanBeFlushed()
    {
        $builder = new Builder($this->getMockQueryBuilder());

        $builder->setEagerLoads(['foo']);

        $this->assertSame(['foo'], $builder->getEagerLoads());

        $builder->withoutEagerLoads();

        $this->assertEmpty($builder->getEagerLoads());
    }

    public function testRelationshipEagerLoadProcess()
    {
        $builder = Double::for(Builder::class)->passthru(new Builder($this->getMockQueryBuilder()));
        $builder->setEagerLoads(['orders' => function ($query) {
            $_SERVER['__eloquent.constrain'] = $query;
        }]);
        $relation = Double::for(Relation::class);
        $relation->expects('addEagerConstraints')->with(['models']);
        $relation->expects('initRelation')->with(['models'], 'orders')->returns(['models']);
        $relation->expects('getEager')->returns($eager = new Collection(['results']));
        $relation->expects('match')->with(['models'], $eager, 'orders')->returns(['models.matched']);
        $builder->expects('getRelation')->with('orders')->returns($relation);
        $results = $builder->eagerLoadRelations(['models']);

        $this->assertEquals(['models.matched'], $results);
        $this->assertEquals($relation, $_SERVER['__eloquent.constrain']);
        unset($_SERVER['__eloquent.constrain']);
    }

    public function testRelationshipEagerLoadProcessForImplicitlyEmpty()
    {
        $model = new EloquentBuilderTestModelSelfRelatedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $constrained = null;

        $models = [
            new EloquentBuilderTestModelSelfRelatedStub,
            new EloquentBuilderTestModelSelfRelatedStub,
        ];

        $model->newQuery()->with(['parentFoo' => function ($query) use (&$constrained) {
            $constrained = $query;
        }])->eagerLoadRelations($models);

        $this->assertNotNull($constrained);
        $this->assertTrue($models[0]->relationLoaded('parentFoo'));
        $this->assertNull($models[0]->parentFoo);
        $this->assertNull($models[1]->parentFoo);
    }

    public function testGetRelationProperlySetsNestedRelationships()
    {
        $model = new EloquentBuilderTestModelSelfRelatedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $builder = $model->newQuery();
        $builder->setEagerLoads(['childFoos' => null, 'childFoos.parentFoo' => null, 'childFoos.parentFoo.childFoo' => null]);

        $relation = $builder->getRelation('childFoos');

        $this->assertSame(['parentFoo', 'parentFoo.childFoo'], array_keys($relation->getQuery()->getEagerLoads()));
    }

    public function testGetRelationProperlySetsNestedRelationshipsWithSimilarNames()
    {
        $model = new EloquentBuilderTestModelSelfRelatedStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $builder = $model->newQuery();
        $builder->setEagerLoads(['childFoo' => null, 'childFoos' => null, 'childFoos.parentFoo' => null, 'childFoos.parentFoo.childFoo' => null]);

        $relation = $builder->getRelation('childFoo');
        $groupsRelation = $builder->getRelation('childFoos');

        $this->assertSame([], array_keys($relation->getQuery()->getEagerLoads()));
        $this->assertSame(['parentFoo', 'parentFoo.childFoo'], array_keys($groupsRelation->getQuery()->getEagerLoads()));
    }

    public function testGetRelationThrowsException()
    {
        $this->expectException(RelationNotFoundException::class);

        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $model->newQuery()->getRelation('invalid');
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
        $builder->with('orders', null);
        $eagers = $builder->getEagerLoads();

        $this->assertEquals(['orders'], array_keys($eagers));
        $this->assertInstanceOf(Closure::class, $eagers['orders']);

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
        BaseBuilder::macro('foobar', fn () => 'foo');

        try {
            $builder = $this->getBuilder();

            $this->assertInstanceOf(Builder::class, $builder->foobar());
        } finally {
            BaseBuilder::flushMacros();
        }

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insert')->with(['bar'])->returns('foo');

        $this->assertSame('foo', $builder->insert(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertOrIgnore')->with(['bar'])->returns('foo');

        $this->assertSame('foo', $builder->insertOrIgnore(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertOrIgnoreReturning')->with(['bar'], ['baz'])->returns('foo');

        $this->assertSame('foo', $builder->insertOrIgnoreReturning(['bar'], ['baz']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertOrIgnoreUsing')->with(['bar'], 'baz')->returns('foo');

        $this->assertSame('foo', $builder->insertOrIgnoreUsing(['bar'], 'baz'));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertGetId')->with(['bar'])->returns('foo');

        $this->assertSame('foo', $builder->insertGetId(['bar']));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('insertUsing')->with(['bar'], 'baz')->returns('foo');

        $this->assertSame('foo', $builder->insertUsing(['bar'], 'baz'));

        $builder = $this->getBuilder();
        $builder->getQuery()->expects('raw')->with('bar')->returns('foo');

        $this->assertSame('foo', $builder->raw('bar'));
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

    public function testWhereNotWithClosure()
    {
        $model = new EloquentBuilderTestStub();
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->whereNot(function ($query) {
            $query->where('baz', '>', 9000);
        });

        $this->assertSame('select * from "table" where not ("baz" > ?)', $query->toSql());
        $this->assertEquals([9000], $query->getBindings());
    }

    public function testSimpleOrWhereNot()
    {
        $model = new EloquentBuilderTestStub();
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->orWhereNot('name', 'foo')->orWhereNot('name', '<>', 'bar');
        $this->assertSame('select * from "table" where not "name" = ? or not "name" <> ?', $query->toSql());
        $this->assertEquals(['foo', 'bar'], $query->getBindings());
    }

    public function testWhereNotWithArrayConditions()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $query = $model->newQuery()->whereNot(['foo' => 1, 'bar' => 2]);
        $this->assertSame('select * from "table" where not (("foo" = ? and "bar" = ?))', $query->toSql());
        $this->assertEquals([1, 2], $query->getBindings());

        $query = $model->newQuery()->whereNot([['foo', 1], ['bar', '<', 2]]);
        $this->assertSame('select * from "table" where not (("foo" = ? and "bar" < ?))', $query->toSql());
        $this->assertEquals([1, 2], $query->getBindings());

        $query = $model->newQuery()->where('baz', 3)->orWhereNot(['foo' => 1, 'bar' => 2]);
        $this->assertSame('select * from "table" where "baz" = ? or not (("foo" = ? or "bar" = ?))', $query->toSql());
        $this->assertEquals([3, 1, 2], $query->getBindings());
    }

    public function testOrWhereNotWithClosure()
    {
        $model = new EloquentBuilderTestStub();
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->where('foo', 'bar')->orWhereNot(function ($query) {
            $query->where('baz', '>', 9000);
        });

        $this->assertSame('select * from "table" where "foo" = ? or not ("baz" > ?)', $query->toSql());
        $this->assertEquals(['bar', 9000], $query->getBindings());
    }

    public function testRealQueryDynamicScopesWithNamedArguments()
    {
        $model = new EloquentBuilderTestDynamicScopeStub;
        $this->mockConnectionForModel($model, 'SQLite');
        $query = $model->newQuery()->dynamic(bar: 'baz');
        $this->assertSame('select * from "table" where "foo" = ?', $query->toSql());
        $this->assertEquals(['baz'], $query->getBindings());
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
        $builder->expects('from')->with('eloquent_builder_test_where_belongs_to_stubs');
        $builder->setModel($related);
        $builder->getQuery()->expects('whereIn')->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', [2], 'and');

        $result = $builder->whereBelongsTo($parent);
        $this->assertEquals($result, $builder);

        $builder = $this->getBuilder();
        $builder->expects('from')->with('eloquent_builder_test_where_belongs_to_stubs');
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
        $builder->expects('from')->with('eloquent_builder_test_where_belongs_to_stubs');
        $builder->setModel($related);
        $builder->getQuery()->expects('whereIn')->with('eloquent_builder_test_where_belongs_to_stubs.parent_id', [2, 3], 'and');

        $result = $builder->whereBelongsTo($parents);
        $this->assertEquals($result, $builder);

        $builder = $this->getBuilder();
        $builder->expects('from')->with('eloquent_builder_test_where_belongs_to_stubs');
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
        $aliasRegex = '/\b(laravel_reserved_\d+)(\b|$)/i';

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
        $aliasRegex = '/\b(laravel_reserved_\d+)(\b|$)/i';

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

        $connection->expects('select')->returns([
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

        $connection->expects('select')->returns([
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
        $aliasRegex = '/\b(laravel_reserved_\d+)(\b|$)/i';

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
        $aliasRegex = '/\b(laravel_reserved_\d+)(\b|$)/i';

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

    public function testWhereMorphedToWithCustomOwnerKey()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;
        $relatedModel->uuid = 'related-uuid';

        $builder = $model->whereMorphedTo('morphWithOwnerKey', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), 'related-uuid'], $builder->getBindings());
    }

    public function testWhereMorphedToCollectionWithCustomOwnerKey()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $firstRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $firstRelatedModel->id = 1;
        $firstRelatedModel->uuid = 'first-uuid';

        $secondRelatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $secondRelatedModel->id = 2;
        $secondRelatedModel->uuid = 'second-uuid';

        $builder = $model->whereMorphedTo('morphWithOwnerKey', new Collection([$firstRelatedModel, $secondRelatedModel]));

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where (("eloquent_builder_test_model_parent_stubs"."morph_type" = ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?, ?)))', $builder->toSql());
        $this->assertEquals([$firstRelatedModel->getMorphClass(), 'first-uuid', 'second-uuid'], $builder->getBindings());
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

    public function testWhereNotMorphedToWithCustomOwnerKey()
    {
        $model = new EloquentBuilderTestModelParentStub;
        $this->mockConnectionForModel($model, '');

        $relatedModel = new EloquentBuilderTestModelCloseRelatedStub;
        $relatedModel->id = 1;
        $relatedModel->uuid = 'related-uuid';

        $builder = $model->whereNotMorphedTo('morphWithOwnerKey', $relatedModel);

        $this->assertSame('select * from "eloquent_builder_test_model_parent_stubs" where not (("eloquent_builder_test_model_parent_stubs"."morph_type" is not distinct from ? and "eloquent_builder_test_model_parent_stubs"."morph_id" in (?)))', $builder->toSql());
        $this->assertEquals([$relatedModel->getMorphClass(), 'related-uuid'], $builder->getBindings());
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

        $model->expects('getKeyType')->returns('int');
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

        $builder->getQuery()->expects('where')->with($keyName, '=', Argument::satisfies(function ($argument) {
            return $argument === null;
        }));

        $builder->whereKey(null);
    }

    public function testWhereKeyMethodWithModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('where')->with($keyName, '=', Argument::satisfies(function ($argument) {
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

        $builder->getQuery()->expects('where')->with($keyName, '!=', Argument::satisfies(function ($argument) {
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

        $model->expects('getKeyType')->returns('int');
        $builder->getQuery()->expects('where')->with($keyName, '!=', $int);

        $builder->whereKeyNot($int);
    }

    public function testWhereKeyNotMethodWithModel()
    {
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder = $this->getBuilder()->setModel($model);
        $keyName = $model->getQualifiedKeyName();

        $builder->getQuery()->expects('where')->with($keyName, '!=', Argument::satisfies(function ($argument) {
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

    public function testWhereKeyMethodWithArrayAndCollection()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $this->assertSame('select * from "table" where "table"."id" in (1, 2, 3)', $model->newQuery()->whereKey([1, 2, 3])->toSql());
        $this->assertSame('select * from "table" where "table"."id" in (1, 2, 3)', $model->newQuery()->whereKey(new Collection([1, 2, 3]))->toSql());
    }

    public function testWhereKeyNotMethodWithArrayAndCollection()
    {
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, 'SQLite');

        $this->assertSame('select * from "table" where "table"."id" not in (1, 2, 3)', $model->newQuery()->whereKeyNot([1, 2, 3])->toSql());
        $this->assertSame('select * from "table" where "table"."id" not in (1, 2, 3)', $model->newQuery()->whereKeyNot(new Collection([1, 2, 3]))->toSql());
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

        $builder->getQuery()->expects('where')->with($keyName, '!=', Argument::satisfies(function ($argument) {
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

        $builder->getQuery()->expects('whereNotIn')->with($keyName, Argument::satisfies(function ($argument) {
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

        $builder->getQuery()->expects('whereNotIn')->with($keyName, Argument::satisfies(function ($argument) {
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
        $model->expects('getCreatedAtColumn')->returns('foo');
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('latest')->with('foo');

        $builder->latest();
    }

    public function testLatestWithoutColumnWithoutCreatedAt()
    {
        $model = $this->getMockModel();
        $model->expects('getCreatedAtColumn')->returns(null);
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
        $model->expects('getCreatedAtColumn')->returns('foo');
        $builder = $this->getBuilder()->setModel($model);

        $builder->getQuery()->expects('oldest')->with('foo');

        $builder->oldest();
    }

    public function testOldestWithoutColumnWithoutCreatedAt()
    {
        $model = $this->getMockModel();
        $model->expects('getCreatedAtColumn')->returns(null);
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

        $connection = Double::for(Connection::class);
        $connection->allows('getTablePrefix')->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')->with('update "table" set "foo" = ?, "table"."updated_at" = ?', ['bar', $now])->returns(1);

        $result = $builder->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithTimestampValue()
    {
        $connection = Double::for(Connection::class);
        $connection->expects('getTablePrefix')->times(2)->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')->with('update "table" set "foo" = ?, "table"."updated_at" = ?', ['bar', null])->returns(1);

        $result = $builder->update(['foo' => 'bar', 'updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithQualifiedTimestampValue()
    {
        $connection = Double::for(Connection::class);
        $connection->allows('getTablePrefix')->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')->with('update "table" set "table"."foo" = ?, "table"."updated_at" = ?', ['bar', null])->returns(1);

        $result = $builder->update(['table.foo' => 'bar', 'table.updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithoutTimestamp()
    {
        $connection = Double::for(Connection::class);
        $connection->expects('getTablePrefix')->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubWithoutTimestamp;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')->with('update "table" set "foo" = ?', ['bar'])->returns(1);

        $result = $builder->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithAlias()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $connection = Double::for(Connection::class);
        $connection->allows('getTablePrefix')->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')->with('update "table" as "alias" set "foo" = ?, "alias"."updated_at" = ?', ['bar', $now])->returns(1);

        $result = $builder->from('table as alias')->update(['foo' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testUpdateWithAliasWithQualifiedTimestampValue()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $connection = Double::for(Connection::class);
        $connection->allows('getTablePrefix')->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
        $builder = new Builder($query);
        $model = new EloquentBuilderTestStub;
        $this->mockConnectionForModel($model, '');
        $builder->setModel($model);
        $builder->getConnection()->expects('update')->with('update "table" as "alias" set "foo" = ?, "alias"."updated_at" = ?', ['bar', null])->returns(1);

        $result = $builder->from('table as alias')->update(['foo' => 'bar', 'alias.updated_at' => null]);
        $this->assertEquals(1, $result);
    }

    public function testUpsert()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->returns('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('upsert')->with([
            ['updated_at' => $now, 'created_at' => $now, 'email' => 'foo', 'name' => 'bar'],
            ['updated_at' => $now, 'created_at' => $now, 'name' => 'bar2', 'email' => 'foo2'],
        ], ['email'], ['email', 'name', 'updated_at'])->returns(2);

        $result = $builder->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], ['email']);

        $this->assertEquals(2, $result);
    }

    public function testTouch()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->returns('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('update')->with(['updated_at' => Carbon::now()])->returns(2);

        $result = $builder->touch();

        $this->assertEquals(2, $result);
    }

    public function testTouchWithCustomColumn()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->returns('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('update')->with(['published_at' => Carbon::now()])->returns(2);

        $result = $builder->touch('published_at');

        $this->assertEquals(2, $result);
    }

    public function testTouchWithMultipleColumns()
    {
        Carbon::setTestNow($now = '2017-10-10 10:10:10');

        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table')->returns('foo_table');
        $query->from = 'foo_table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubStringPrimaryKey;
        $builder->setModel($model);

        $query->expects('update')->with(['published_at' => Carbon::now(), 'verified_at' => Carbon::now()])->returns(2);

        $result = $builder->touch(['published_at', 'verified_at']);

        $this->assertEquals(2, $result);
    }

    public function testTouchWithoutUpdatedAtColumn()
    {
        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('table')->returns('table');
        $query->from = 'table';

        $builder = new Builder($query);
        $model = new EloquentBuilderTestStubWithoutTimestamp;
        $builder->setModel($model);

        $query->expects('update')->never();

        $result = $builder->touch();

        $this->assertFalse($result);
    }

    public function testClone()
    {
        $connection = Double::for(Connection::class);
        $connection->expects('getTablePrefix')->times(2)->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
        $builder = new Builder($query);
        $builder->select('*')->from('users');
        $clone = $builder->clone()->where('email', 'foo');

        $this->assertNotSame($builder, $clone);
        $this->assertSame('select * from "users"', $builder->toSql());
        $this->assertSame('select * from "users" where "email" = ?', $clone->toSql());
    }

    public function testCloneModelMakesAFreshCopyOfTheModel()
    {
        $connection = Double::for(Connection::class);
        $connection->expects('getTablePrefix')->times(2)->returns('');
        $query = new BaseBuilder($connection, new Grammar($connection), new Processor);
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
        $query = Double::for(BaseBuilder::class);
        $query->expects('toRawSql')->returns('select * from "users" where "email" = \'foo\'');

        $builder = new Builder($query);

        $this->assertSame('select * from "users" where "email" = \'foo\'', $builder->toRawSql());
    }

    public function testPassthruMethodsCallsAreNotCaseSensitive()
    {
        $query = Double::for(BaseBuilder::class);

        $mockResponse = 'select 1';
        $query->expects('toRawSql')->returns($mockResponse)->times(3);

        $builder = new Builder($query);

        $this->assertSame('select 1', $builder->TORAWSQL());
        $this->assertSame('select 1', $builder->toRawSql());
        $this->assertSame('select 1', $builder->toRawSQL());
    }

    public function testPassthruArrayElementsMustAllBeLowercase()
    {
        $builder = new class(Double::for(BaseBuilder::class)) extends Builder
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
        $connection = Double::for(Connection::class);
        $connection->allows('getPostProcessor')->returns($processor);
        $grammar = new $grammarClass($connection);
        $connection->allows('getQueryGrammar')->returns($grammar);
        $connection->allows('getTablePrefix')->returns('');
        $connection->allows('query')->resolves(function () use ($connection, $grammar, $processor) {
            return new BaseBuilder($connection, $grammar, $processor);
        });
        $connection->allows('getDatabaseName')->returns('database');
        $resolver = new ConnectionResolver(['default' => $connection]);
        $resolver->setDefaultConnection('default');
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
        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table');
        $query->from = 'foo_table';
        $query->expects('incrementEach')->with(Argument::all(function ($columns, $extra) {
            return $columns === ['votes' => 5] && array_key_exists('foo_table.updated_at', $extra);
        }))->returns(1);

        $builder = new Builder($query);
        $model = $this->getMockModel();
        $model->expects('usesTimestamps')->returns(true);
        $model->expects('getUpdatedAtColumn')->times(2)->returns('updated_at');
        $model->expects('freshTimestampString')->returns('2026-03-26 00:00:00');
        $model->expects('hasSetMutator')->returns(false);
        $model->expects('hasAttributeSetMutator')->returns(false);
        $model->expects('hasCast')->returns(false);
        $builder->setModel($model);

        $result = $builder->incrementEach(['votes' => 5]);
        $this->assertEquals(1, $result);
    }

    public function testDecrementEachCallsToBaseWithUpdatedAt()
    {
        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table');
        $query->from = 'foo_table';
        $query->expects('decrementEach')->with(Argument::all(function ($columns, $extra) {
            return $columns === ['votes' => 3] && array_key_exists('foo_table.updated_at', $extra);
        }))->returns(1);

        $builder = new Builder($query);
        $model = $this->getMockModel();
        $model->expects('usesTimestamps')->returns(true);
        $model->expects('getUpdatedAtColumn')->times(2)->returns('updated_at');
        $model->expects('freshTimestampString')->returns('2026-03-26 00:00:00');
        $model->expects('hasSetMutator')->returns(false);
        $model->expects('hasAttributeSetMutator')->returns(false);
        $model->expects('hasCast')->returns(false);
        $builder->setModel($model);

        $result = $builder->decrementEach(['votes' => 3]);
        $this->assertEquals(1, $result);
    }

    public function testIncrementEachWithoutTimestamps()
    {
        $query = Double::for(BaseBuilder::class);
        $query->expects('from')->with('foo_table');
        $query->expects('incrementEach')->with(['votes' => 1], [])->returns(1);

        $builder = new Builder($query);
        $model = $this->getMockModel();
        $model->expects('usesTimestamps')->returns(false);
        $builder->setModel($model);

        $result = $builder->incrementEach(['votes' => 1]);
        $this->assertEquals(1, $result);
    }

    protected function getMockModel()
    {
        $model = Double::for(Model::class);
        $model->allows('getKeyName')->returns('foo');
        $model->allows('getTable')->returns('foo_table');
        $model->allows('getQualifiedKeyName')->returns('foo_table.foo');

        return $model;
    }

    protected function newPagedBuilder(array $ids, string $column = 'id'): array
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table "table" ("'.$column.'" integer primary key)');

        foreach ($ids as $id) {
            $pdo->exec('insert into "table" values ('.$id.')');
        }

        $connection = new SQLiteConnection($pdo);
        $connection->enableQueryLog();

        return [$connection, $this->newBuilder($connection)];
    }

    protected function newBinaryUuidPagedBuilder(): array
    {
        $ids = [
            '00000000-0000-0000-0000-000000000001',
            '00000000-0000-0000-0000-000000000002',
            '00000000-0000-0000-0000-000000000003',
        ];

        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table "table" ("id" blob primary key)');

        foreach ($ids as $id) {
            $pdo->prepare('insert into "table" values (?)')->execute([hex2bin(str_replace('-', '', $id))]);
        }

        $connection = new SQLiteConnection($pdo);
        $connection->enableQueryLog();

        $resolver = new ConnectionResolver(['default' => $connection]);
        $resolver->setDefaultConnection('default');
        EloquentBuilderTestBinaryUuidStub::setConnectionResolver($resolver);

        $builder = (new Builder($connection->query()))->setModel(new EloquentBuilderTestBinaryUuidStub);

        return [$connection, $builder, $ids];
    }

    protected function pagedBindings(SQLiteConnection $connection): array
    {
        return array_column($connection->getQueryLog(), 'bindings');
    }

    protected function newConnection(): SQLiteConnection
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table "table" ("id" integer primary key, "name" text, "age" integer)');
        $pdo->exec("insert into \"table\" values (1, 'taylor', 26)");
        $pdo->exec("insert into \"table\" values (2, 'dayle', 28)");

        $connection = new SQLiteConnection($pdo);
        $connection->enableQueryLog();

        return $connection;
    }

    protected function newBuilder(SQLiteConnection $connection): Builder
    {
        $resolver = new ConnectionResolver(['default' => $connection]);
        $resolver->setDefaultConnection('default');
        EloquentBuilderTestStub::setConnectionResolver($resolver);

        return (new Builder($connection->query()))->setModel(new EloquentBuilderTestStub);
    }

    protected function getMockQueryBuilder()
    {
        $query = Double::for(BaseBuilder::class);
        $query->allows('from')->with('foo_table');

        return $query;
    }
}

class EloquentBuilderTestStub extends Model
{
    protected $table = 'table';
}

class EloquentBuilderTestBinaryUuidStub extends Model
{
    protected $table = 'table';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['id' => AsBinary::uuid()];
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

class EloquentBuilderTestDynamicScopeStub extends Model
{
    protected $table = 'table';

    public function scopeDynamic($query, $foo = 'foo', $bar = 'bar')
    {
        $query->where($foo, $bar);
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

    public function morphWithOwnerKey()
    {
        return $this->morphTo('morph', null, null, 'uuid');
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

class EloquentBuilderEagerLoadSpy extends Builder
{
    public array $eagerLoaded = [];

    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        $this->eagerLoaded[] = [$models, $name, $constraints];

        return ['foo'];
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
