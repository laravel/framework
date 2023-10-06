<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyThroughTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2023-01-01 00:00:00');
    }

    public function testCreateOrFirstMethodCreatesNewRecord(): void
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');
            $parent->getConnection()->expects('insert')->with(
                'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
                ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
            )->andReturnTrue();

            $result = $parent->children()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
            $this->assertEquals([
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ], $result->toArray());
        });
    }

    public function testCreateOrFirstMethodRetrievesExistingRecord(): void
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $parent->id = '123';
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

            $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
            $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

            $parent->getConnection()
                ->expects('insert')
                ->with($sql, $bindings)
                ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

            $parent->getConnection()
                ->expects('select')
                ->with(
                    'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."child_id" = "child"."id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                    ['123', 'foo'],
                    true,
                )
                ->andReturn([[
                    'parent_id' => '123',
                    'attr' => 'foo',
                    'val' => 'bar',
                    'created_at' => '2023-01-01 00:00:00',
                    'updated_at' => '2023-01-01 00:00:00',
                ]]);

            $result = $parent->children()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
            $this->assertEquals([
                'parent_id' => '123',
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ], $result->toArray());
        });
    }

    public function testFirstOrCreateMethodCreatesNewRecord(): void
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $parent->id = '123';
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

            $parent->getConnection()
                ->expects('select')
                ->with(
                    'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."child_id" = "child"."id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                    ['123', 'foo'],
                    true,
                )
                ->andReturn([]);

            $parent->getConnection()->expects('insert')->with(
                'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
                ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
            )->andReturnTrue();

            $result = $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
            $this->assertEquals([
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ], $result->toArray());
        });
    }

    public function testFirstOrCreateMethodRetrievesExistingRecord(): void
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $parent->id = '123';
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

            $parent->getConnection()
                ->expects('select')
                ->with(
                    'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."child_id" = "child"."id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                    ['123', 'foo'],
                    true,
                )
                ->andReturn([[
                    'parent_id' => '123',
                    'attr' => 'foo',
                    'val' => 'bar',
                    'created_at' => '2023-01-01 00:00:00',
                    'updated_at' => '2023-01-01 00:00:00',
                ]]);

            $result = $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
            $this->assertEquals([
                'parent_id' => '123',
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ], $result->toArray());
        });
    }

    public function testFirstOrCreateMethodRetrievesRecordCreatedJustNow()
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $parent->id = '123';
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

            $parent->getConnection()
                ->expects('select')
                ->with(
                    'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."child_id" = "child"."id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                    ['123', 'foo'],
                    true,
                )
                ->andReturn([]);

            $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
            $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

            $parent->getConnection()
                ->expects('insert')
                ->with($sql, $bindings)
                ->andThrow(new QueryException('Integrity constraint violation', $sql, $bindings, new Exception()));

            // Verify that it is directly thrown without being converted into retries or custom exceptions
            $this->expectException(QueryException::class);
            $this->expectExceptionMessage('Integrity constraint violation');
            $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        });
    }

    public function testUpdateOrCreateMethodCreatesNewRecord()
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $parent->id = '123';
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

            $parent->getConnection()
                ->expects('select')
                ->with(
                    'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."child_id" = "child"."id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                    ['123', 'foo'],
                    true,
                )
                ->andReturn([]);

            $parent->getConnection()
                ->expects('insert')
                ->with(
                    'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
                    ['foo', 'baz', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
                )
                ->andReturnTrue();

            $result = $parent->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
            $this->assertEquals([
                'attr' => 'foo',
                'val' => 'baz',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ], $result->toArray());
        });
    }

    public function testUpdateOrCreateMethodUpdatesExistingRecord()
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $parent->id = '123';
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

            $parent->getConnection()
                ->expects('select')
                ->with(
                    'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."child_id" = "child"."id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                    ['123', 'foo'],
                    true,
                )
                ->andReturn([[
                    'id' => '123',
                    'attr' => 'foo',
                    'val' => 'bar',
                    'created_at' => '2023-01-01T00:00:00.000000Z',
                    'updated_at' => '2023-01-01T00:00:00.000000Z',
                ]]);

            $parent->getConnection()
                ->expects('update')
                ->with(
                    'update "child" set "val" = ?, "updated_at" = ? where "id" = ?',
                    ['baz', '2023-01-01 00:00:00', '123'],
                )
                ->andReturn(1);

            $result = $parent->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
            $this->assertEquals([
                'id' => '123',
                'attr' => 'foo',
                'val' => 'baz',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ], $result->toArray());
        });
    }

    public function testUpdateOrCreateMethodUpdatesRecordCreatedJustNow(): void
    {
        Model::unguarded(function () {
            $parent = new HasManyThroughParent();
            $parent->id = '123';
            $this->mockConnectionForModel($parent, 'SQLite');
            $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
            $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

            $parent->getConnection()
                ->expects('select')
                ->with(
                    'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."child_id" = "child"."id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                    ['123', 'foo'],
                    true,
                )
                ->andReturn([]);

            $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
            $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

            $parent->getConnection()
                ->expects('insert')
                ->with($sql, $bindings)
                ->andThrow(new QueryException('Integrity constraint violation', $sql, $bindings, new Exception()));

            // Verify that it is directly thrown without being converted into retries or custom exceptions
            $this->expectException(QueryException::class);
            $this->expectExceptionMessage('Integrity constraint violation');
            $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        });
    }

    protected function mockConnectionForModel(Model $model, string $database): void
    {
        $grammarClass = 'Illuminate\Database\Query\Grammars\\'.$database.'Grammar';
        $processorClass = 'Illuminate\Database\Query\Processors\\'.$database.'Processor';
        $grammar = new $grammarClass;
        $processor = new $processorClass;
        $connection = Mockery::mock(ConnectionInterface::class, ['getQueryGrammar' => $grammar, 'getPostProcessor' => $processor]);
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new Builder($connection, $grammar, $processor);
        });
        $connection->shouldReceive('getDatabaseName')->andReturn('database');
        $resolver = Mockery::mock(ConnectionResolverInterface::class, ['connection' => $connection]);
        $class = get_class($model);
        $class::setConnectionResolver($resolver);
    }
}

/**
 * @property string $id
 */
class HasManyThroughChild extends Model
{
    public $incrementing = false;

    protected $table = 'child';

    protected $keyType = 'string';
}

/**
 * @property string $id
 * @property string $parent_id
 * @property string $child_id
 */
class HasManyThroughPivot extends Model
{
    public $incrementing = false;

    protected $table = 'pivot';

    protected $keyType = 'string';
}

/**
 * @property string $id
 */
class HasManyThroughParent extends Model
{
    public $incrementing = false;

    protected $table = '';

    protected $keyType = 'string';

    public function children(): HasManyThrough
    {
        return $this->hasManyThrough(
            HasManyThroughChild::class,
            HasManyThroughPivot::class,
            'parent_id',
            'id',
            'id',
            'child_id',
        );
    }
}

