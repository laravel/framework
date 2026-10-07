<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
use Closure;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyThroughCreateOrFirstTest extends TestCase
{
    protected function setUp(): void
    {
        Carbon::setTestNow('2023-01-01 00:00:00');
    }

    #[DataProvider('createOrFirstValues')]
    public function testCreateOrFirstMethodCreatesNewRecord(Closure|array $values): void
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel();
        $parent->id = 123;
        $this->mockConnectionForModel($parent, 'SQLite', [789]);
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');
        $parent->getConnection()->expects('insert')->with('insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

        $result = $parent->children()->createOrFirst(['attr' => 'foo'], $values);
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
        $parent->exists = true;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');

        $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $parent->getConnection()->expects('insert')->with($sql, $bindings)->throws(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
                [])->returns([[
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
        $parent->exists = true;
        $this->mockConnectionForModel($parent, 'SQLite', [789]);
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
                [])->returns([]);

        $parent->getConnection()->expects('insert')->with('insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

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
        $parent->exists = true;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
                [])->returns([[
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
        $parent->exists = true;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
                [])->returns([]);

        $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $parent->getConnection()->expects('insert')->with($sql, $bindings)->throws(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ? and "val" = ?) limit 1',
                [123, 'foo', 'bar'],
                true,
                [])->returns([[
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
        $parent->exists = true;
        $this->mockConnectionForModel($parent, 'SQLite', [789]);
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
                [])->returns([]);

        $parent->getConnection()->expects('insert')->with('insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)',
                ['foo', 'baz', '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

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
        $parent->exists = true;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
                [])->returns([[
                'id' => 789,
                'pivot_id' => 456,
                'laravel_through_key' => 123,
                'attr' => 'foo',
                'val' => 'bar',
                'created_at' => '2023-01-01T00:00:00.000000Z',
                'updated_at' => '2023-01-01T00:00:00.000000Z',
            ]]);

        $parent->getConnection()->expects('update')->with('update "child" set "val" = ?, "updated_at" = ? where "id" = ?',
                ['baz', '2023-01-01 00:00:00', 789])->returns(1);

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
        $parent->exists = true;
        $this->mockConnectionForModel($parent, 'SQLite');
        $parent->getConnection()->allows('transactionLevel')->returns(0);
        $parent->getConnection()->allows('getName')->returns('sqlite');

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ?) limit 1',
                [123, 'foo'],
                true,
                [])->returns([]);

        $sql = 'insert into "child" ("attr", "val", "updated_at", "created_at") values (?, ?, ?, ?)';
        $bindings = ['foo', 'bar', '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $parent->getConnection()->expects('insert')->with($sql, $bindings)->throws(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $parent->getConnection()->expects('select')->with('select "child".*, "pivot"."parent_id" as "laravel_through_key" from "child" inner join "pivot" on "pivot"."id" = "child"."pivot_id" where "pivot"."parent_id" = ? and ("attr" = ? and "val" = ?) limit 1',
                [123, 'foo', 'bar'],
                true,
                [])->returns([[
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

    public static function createOrFirstValues(): array
    {
        return [
            'array' => [['val' => 'bar']],
            'closure' => [fn () => ['val' => 'bar']],
        ];
    }

    protected function mockConnectionForModel(Model $model, string $database, array $lastInsertIds = []): void
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
            return new Builder($connection, $grammar, $processor);
        });
        $connection->allows('getDatabaseName')->returns('database');
        $resolver = Double::for(ConnectionResolverInterface::class);
        $resolver->allows('connection')->returns($connection);

        $class = get_class($model);
        $class::setConnectionResolver($resolver);

        $pdo = Double::for(PDO::class);
        $connection->allows('getPdo')->returns($pdo);

        foreach ($lastInsertIds as $id) {
            $pdo->expects('lastInsertId')->returns($id);
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
