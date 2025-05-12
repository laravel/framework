<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyCreateOrFirstTest extends TestCase
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

    public function testCreateOrFirstMethodCreatesNewRelated(): void
    {
        $source = new BelongsToManyCreateOrFirstTestSourceModel();
        $source->id = 123;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
            [456],
        );
        $source->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $source->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $source->getConnection()->expects('insert')->with(
            'insert into "related_table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'],
        )->andReturnTrue();

        $source->getConnection()->expects('insert')->with(
            'insert into "pivot_table" ("related_id", "source_id") values (?, ?)',
            [456, 123],
        )->andReturnTrue();

        $result = $source->related()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testCreateOrFirstMethodAssociatesExistingRelated(): void
    {
        $source = new BelongsToManyCreateOrFirstTestSourceModel();
        $source->id = 123;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
        );
        $source->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $source->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $sql = 'insert into "related_table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $source->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $source->getConnection()
            ->expects('select')
            ->with('select * from "related_table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([[
                'id' => 456,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $source->getConnection()->expects('insert')->with(
            'insert into "pivot_table" ("related_id", "source_id") values (?, ?)',
            [456, 123],
        )->andReturnTrue();

        $result = $source->related()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            // Pivot is not loaded when related model is newly created.
            'id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesExistingRelatedAlreadyAssociated(): void
    {
        $source = new BelongsToManyCreateOrFirstTestSourceModel();
        $source->id = 123;
        $source->exists = true;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
        );
        $source->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $source->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $source->getConnection()
            ->expects('select')
            ->with(
                'select "related_table".*, "pivot_table"."source_id" as "pivot_source_id", "pivot_table"."related_id" as "pivot_related_id" from "related_table" inner join "pivot_table" on "related_table"."id" = "pivot_table"."related_id" where "pivot_table"."source_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([[
                'id' => 456,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
                'pivot_source_id' => 123,
                'pivot_related_id' => 456,
            ]]);

        $result = $source->related()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
            'pivot' => [
                'source_id' => 123,
                'related_id' => 456,
            ],
        ], $result->toArray());
    }

    public function testCreateOrFirstMethodRetrievesExistingRelatedAssociatedJustNow(): void
    {
        $source = new BelongsToManyCreateOrFirstTestSourceModel();
        $source->id = 123;
        $source->exists = true;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
        );
        $source->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $source->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $sql = 'insert into "related_table" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $source->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $source->getConnection()
            ->expects('select')
            ->with('select * from "related_table" where ("attr" = ?) limit 1', ['foo'], true)
            ->andReturn([[
                'id' => 456,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $sql = 'insert into "pivot_table" ("related_id", "source_id") values (?, ?)';
        $bindings = [456, 123];

        $source->getConnection()
            ->expects('insert')
            ->with($sql, $bindings)
            ->andThrow(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $source->getConnection()
            ->expects('select')
            ->with(
                'select "related_table".*, "pivot_table"."source_id" as "pivot_source_id", "pivot_table"."related_id" as "pivot_related_id" from "related_table" inner join "pivot_table" on "related_table"."id" = "pivot_table"."related_id" where "pivot_table"."source_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                false,
            )
            ->andReturn([[
                'id' => 456,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
                'pivot_source_id' => 123,
                'pivot_related_id' => 456,
            ]]);

        $result = $source->related()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
            'pivot' => [
                'source_id' => 123,
                'related_id' => 456,
            ],
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesExistingRelatedAndAssociatesIt(): void
    {
        $source = new BelongsToManyCreateOrFirstTestSourceModel();
        $source->id = 123;
        $source->exists = true;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
        );
        $source->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $source->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $source->getConnection()
            ->expects('select')
            ->with(
                'select "related_table".*, "pivot_table"."source_id" as "pivot_source_id", "pivot_table"."related_id" as "pivot_related_id" from "related_table" inner join "pivot_table" on "related_table"."id" = "pivot_table"."related_id" where "pivot_table"."source_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([]);

        $source->getConnection()
            ->expects('select')
            ->with(
                'select * from "related_table" where ("attr" = ?) limit 1',
                ['foo'],
                true,
            )
            ->andReturn([[
                'id' => 456,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01 00:00:00',
                'updated_at' => '2023-01-01 00:00:00',
            ]]);

        $source->getConnection()
            ->expects('insert')
            ->with(
                'insert into "pivot_table" ("related_id", "source_id") values (?, ?)',
                [456, 123],
            )
            ->andReturnTrue();

        $result = $source->related()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            // Pivot is not loaded when related model is newly created.
            'id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodFallsBackToCreateOrFirst(): void
    {
        $source = new class() extends BelongsToManyCreateOrFirstTestSourceModel
        {
            protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null): BelongsToMany
            {
                $relation = Mockery::mock(BelongsToMany::class)->makePartial();
                $relation->__construct(...func_get_args());
                $instance = new BelongsToManyCreateOrFirstTestRelatedModel([
                    'id' => 456,
                    'attr' => 'foo',
                    'val' => 'bar',
                    'created_at' => '2023-01-01T00:00:00.000000Z',
                    'updated_at' => '2023-01-01T00:00:00.000000Z',
                    'pivot' => [
                        'source_id' => 123,
                        'related_id' => 456,
                    ],
                ]);
                $instance->exists = true;
                $instance->wasRecentlyCreated = false;
                $instance->syncOriginal();
                $relation
                    ->expects('createOrFirst')
                    ->with(['attr' => 'foo'], ['val' => 'bar'], [], true)
                    ->andReturn($instance);

                return $relation;
            }
        };
        $source->id = 123;
        $source->exists = true;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
        );
        $source->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $source->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $source->getConnection()
            ->expects('select')
            ->with(
                'select "related_table".*, "pivot_table"."source_id" as "pivot_source_id", "pivot_table"."related_id" as "pivot_related_id" from "related_table" inner join "pivot_table" on "related_table"."id" = "pivot_table"."related_id" where "pivot_table"."source_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
            )
            ->andReturn([]);

        $source->getConnection()
            ->expects('select')
            ->with(
                'select * from "related_table" where ("attr" = ?) limit 1',
                ['foo'],
                true,
            )
            ->andReturn([]);

        $result = $source->related()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertEquals([
            'id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
            'pivot' => [
                'source_id' => 123,
                'related_id' => 456,
            ],
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodCreatesNewRelated(): void
    {
        $source = new class() extends BelongsToManyCreateOrFirstTestSourceModel
        {
            protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null): BelongsToMany
            {
                $relation = Mockery::mock(BelongsToMany::class)->makePartial();
                $relation->__construct(...func_get_args());
                $instance = new BelongsToManyCreateOrFirstTestRelatedModel([
                    'id' => 456,
                    'attr' => 'foo',
                    'val' => 'bar',
                    'created_at' => '2023-01-01T00:00:00.000000Z',
                    'updated_at' => '2023-01-01T00:00:00.000000Z',
                ]);
                $instance->exists = true;
                $instance->wasRecentlyCreated = true;
                $instance->syncOriginal();
                $relation
                    ->expects('firstOrCreate')
                    ->with(['attr' => 'foo'], ['val' => 'baz'], [], true)
                    ->andReturn($instance);

                return $relation;
            }
        };
        $source->id = 123;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
        );

        $result = $source->related()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
        $this->assertEquals([
            'id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodUpdatesExistingRelated(): void
    {
        $source = new class() extends BelongsToManyCreateOrFirstTestSourceModel
        {
            protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null): BelongsToMany
            {
                $relation = Mockery::mock(BelongsToMany::class)->makePartial();
                $relation->__construct(...func_get_args());
                $instance = new BelongsToManyCreateOrFirstTestRelatedModel([
                    'id' => 456,
                    'attr' => 'foo',
                    'val' => 'bar',
                    'created_at' => '2023-01-01T00:00:00.000000Z',
                    'updated_at' => '2023-01-01T00:00:00.000000Z',
                ]);
                $instance->exists = true;
                $instance->wasRecentlyCreated = false;
                $instance->syncOriginal();
                $relation
                    ->expects('firstOrCreate')
                    ->with(['attr' => 'foo'], ['val' => 'baz'], [], true)
                    ->andReturn($instance);

                return $relation;
            }
        };
        $source->id = 123;
        $this->mockConnectionForModels(
            [$source, new BelongsToManyCreateOrFirstTestRelatedModel()],
            'SQLite',
        );
        $source->getConnection()->shouldReceive('transactionLevel')->andReturn(0);
        $source->getConnection()->shouldReceive('getName')->andReturn('sqlite');

        $source->getConnection()
            ->expects('update')
            ->with(
                'update "related_table" set "val" = ?, "updated_at" = ? where "id" = ?',
                ['baz', '2023-01-01 00:00:00', 456],
            )
            ->andReturn(1);

        $result = $source->related()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
        $this->assertEquals([
            'id' => 456,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    protected function mockConnectionForModels(array $models, string $database, array $lastInsertIds = []): void
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

        foreach ($models as $model) {
            /** @var Model $model */
            $class = get_class($model);
            $class::setConnectionResolver($resolver);
        }

        $connection->shouldReceive('getPdo')->andReturn($pdo = Mockery::mock(PDO::class));

        foreach ($lastInsertIds as $id) {
            $pdo->expects('lastInsertId')->andReturn($id);
        }
    }
}

/**
 * @property int $id
 */
class BelongsToManyCreateOrFirstTestRelatedModel extends Model
{
    protected $table = 'related_table';
    protected $guarded = [];
}

/**
 * @property int $id
 */
class BelongsToManyCreateOrFirstTestSourceModel extends Model
{
    protected $table = 'source_table';
    protected $guarded = [];

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(
            BelongsToManyCreateOrFirstTestRelatedModel::class,
            'pivot_table',
            'source_id',
            'related_id',
        );
    }
}
