<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyThroughCreateOrFirstTest extends TestCase
{
    public function setUp(): void
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
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite', [789]);
        $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');
        $parent->getConnection()->expects('insert')->with(
            'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
        )->andReturnTrue();

        $result = $parent->children()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testCreateOrFirstMethodRetrievesExistingRecord(): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
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
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([[
                'id' => 789,
                'pivot_id' => 456,
                'laravel_through_key' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $result = $parent->children()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'pivot_id' => 456,
            'laravel_through_key' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodCreatesNewRecord(): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite', [789]);
        $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([]);

        $parent->getConnection()->expects('insert')->with(
            'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
        )->andReturnTrue();

        $result = $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesExistingRecord(): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([[
                'id' => 789,
                'pivot_id' => 456,
                'laravel_through_key' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $result = $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'pivot_id' => 456,
            'laravel_through_key' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([]);

        $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $parent->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ? and "val" = ?) limit 1',
                [123, 'foo', 'bar'],
                true,
            )
            ->andReturn([[
                'id' => 789,
                'pivot_id' => 456,
                'laravel_through_key' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ]]);

        $result = $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'pivot_id' => 456,
            'laravel_through_key' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodCreatesNewRecord(): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite', [789]);
        $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
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
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodUpdatesExistingRecord(): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([[
                'id' => 789,
                'pivot_id' => 456,
                'laravel_through_key' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ]]);

        $parent->getConnection()
            ->expects('update')
            ->with(
                'update "child" set "val" = ?, "updated_at" = ? where "id" = ?',
                ['baz', '2023-01-01 00:00:00', 789],
            )
            ->andReturn(1);

        $result = $parent->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'pivot_id' => 456,
            'laravel_through_key' => 123,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodUpdatesRecordCreatedJustNow(): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $parent->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([]);

        $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $parent->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $parent->getConnection()
            ->expects('select')
            ->with(
                'select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ? and "val" = ?) limit 1',
                [123, 'foo', 'bar'],
                true,
            )
            ->andReturn([[
                'id' => 789,
                'pivot_id' => 456,
                'laravel_through_key' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ]]);

        $result = $parent->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 789,
            'pivot_id' => 456,
            'laravel_through_key' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    protected function mockConnectionForModel(Model $model, string $database, array $lastInsertIds = []): void
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

        $connection->shouldReceive('getPdo')->andReturn($pdo = Mockery::mock(PDO::class));

        foreach ($lastInsertIds as $id) {
            $pdo->expects('lastInsertId')->andReturn($id);
        }
    }
}

/**
 * @property int $id
 * @property int $pivot_id
 */
class HasManyThroughCreateOrFirstTestChildModel extends Model
{
    protected $table = 'child';
    protected $guarded = [];
}

/**
 * @property int $id
 * @property int $parent_id
 */
class HasManyThroughCreateOrFirstTestPivotModel extends Model
{
    protected $table = 'pivot';
    protected $guarded = [];
}

/**
 * @property int $id
 */
class HasManyThroughCreateOrFirstTestParentModel extends Model
{
    protected $table = 'parent';
    protected $guarded = [];

    public function children(): HasManyThrough
    {
        return $this->hasManyThrough(
            HasManyThroughCreateOrFirstTestChildModel::class,
            HasManyThroughCreateOrFirstTestPivotModel::class,
            'parent_id',
            'pivot_id',
        );
    }
}
