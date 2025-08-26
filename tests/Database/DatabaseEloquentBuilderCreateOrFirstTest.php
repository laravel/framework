<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBuilderCreateOrFirstTest extends TestCase
{
    protected function setUp(): void
    {
        Carbon::setTestNow('2023-01-01 00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
    }

    public function testCreateOrFirstMethodCreatesNewRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite', [123]);
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()->expects('insert')->with(
            'insert into "table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
        )->andReturnTrue();

        $result = $model->newQuery()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testCreateOrFirstMethodRetrievesExistingRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $sql = 'insert into "table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $model->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], false)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $result = $model->newQuery()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesExistingRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $result = $model->newQuery()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodCreatesNewRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite', [123]);
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([]);

        $model->getConnection()->expects('insert')->with(
            'insert into "table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
        )->andReturnTrue();

        $result = $model->newQuery()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([]);

        $sql = 'insert into "table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $model->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], false)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $result = $model->newQuery()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodUpdatesExistingRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $model->getConnection()
            ->expects('update')
            ->with(
                'update "table" set "val" = ?, "updated_at" = ? where "id" = ?',
                ['baz', '2023-01-01 00:00:00', 123],
            )
            ->andReturn(1);

        $result = $model->newQuery()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodCreatesNewRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite', [123]);
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([]);

        $model->getConnection()->expects('insert')->with(
            'insert into "table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
        )->andReturnTrue();

        $result = $model->newQuery()->updateOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodUpdatesRecordCreatedJustNow(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([]);

        $sql = 'insert into "table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'baz', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $model->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], false)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $model->getConnection()
            ->expects('update')
            ->with(
                'update "table" set "val" = ?, "updated_at" = ? where "id" = ?',
                ['baz', '2023-01-01 00:00:00', 123],
            )
            ->andReturn(1);

        $result = $model->newQuery()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testIncrementOrCreateMethodIncrementsExistingRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'count' => 1,
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $model->getConnection()
            ->expects('raw')
            ->with('"count" + 1')
            ->andReturn('2');

        $model->getConnection()
            ->expects('update')
            ->with(
                'update "table" set "count" = ?, "updated_at" = ? where "id" = ?',
                ['2', '2023-01-01 00:00:00', 123],
            )
            ->andReturn(1);

        $result = $model->newQuery()->incrementOrCreate(['attr' => 'foo'], 'count');
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'count' => 2,
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testIncrementOrCreateMethodCreatesNewRecord(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite', [123]);
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([]);

        $model->getConnection()->expects('insert')->with(
            'insert into "table" ("attr", "count", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', '1', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
        )->andReturnTrue();

        $result = $model->newQuery()->incrementOrCreate(['attr' => 'foo']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'count' => 1,
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testIncrementOrCreateMethodIncrementParametersArePassed(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'count' => 1,
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $model->getConnection()
            ->expects('raw')
            ->with('"count" + 2')
            ->andReturn('3');

        $model->getConnection()
            ->expects('update')
            ->with(
                'update "table" set "count" = ?, "val" = ?, "updated_at" = ? where "id" = ?',
                ['3', 'baz', '2023-01-01 00:00:00', 123],
            )
            ->andReturn(1);

        $result = $model->newQuery()->incrementOrCreate(['attr' => 'foo'], step: 2, extra: ['val' => 'baz']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'count' => 3,
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testIncrementOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        $model = new EloquentBuilderCreateOrFirstTestModel();
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $model->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([]);

        $sql = 'insert into "table" ("attr", "count", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', '1', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $model->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $model->getConnection()
            ->expects('select')
            ->with('select * from "table" where ("attr" = ?) limit 1', ['foo'], false)
            ->andReturn([[
                'id' => 123,
                'attr' => 'foo',
                'count' => 1,
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $model->getConnection()
            ->expects('raw')
            ->with('"count" + 1')
            ->andReturn('2');

        $model->getConnection()
            ->expects('update')
            ->with(
                'update "table" set "count" = ?, "updated_at" = ? where "id" = ?',
                ['2', '2023-01-01 00:00:00', 123],
            )
            ->andReturn(1);

        $result = $model->newQuery()->incrementOrCreate(['attr' => 'foo']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 123,
            'attr' => 'foo',
            'count' => 2,
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    protected function mockConnectionForModel(Model $model, string $database, array $lastInsertIds = []): void
    {
        $grammarClass = 'Illuminate\Database\Query\Grammars\\'.$database.'Grammar';
        $processorClass = 'Illuminate\Database\Query\Processors\\'.$database.'Processor';
        $processor = new $processorClass;
        $connection = Mockery::mock(Connection::class, ['getPostProcessor' => $processor]);
        $grammar = new $grammarClass($connection);
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new Builder($connection, $grammar, $processor);
        });
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $resolver = Mockery::mock(ConnectionResolverInterface::class, ['connection' => $connection]);

        $class = get_class($model);
        $class::setConnectionResolver($resolver);

        $connection->shouldReceive('getPdo')->andReturn($pdo = Mockery::mock(PDO::class));

        foreach ($lastInsertIds as $id) {
            $pdo->expects('lastInsertId')->andReturn($id);
        }
    }
}

class EloquentBuilderCreateOrFirstTestModel extends Model
{
    protected $table = 'table';
    protected $guarded = [];
}
