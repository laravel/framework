<?php

namespace Illuminate\Tests\Database;

use Closure;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyCreateOrFirstTest extends TestCase
{
    use VerifiesDoubles;

    protected function setUp(): void
    {
        Carbon::setTestNow('2023-01-01 00:00:00');
    }

    #[DataProvider('createOrFirstValues')]
    public function testCreateOrFirstMethodCreatesNewRecord(Closure|array $values): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite', [456]);
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->expects('getName')->returns('sqlite');

        $model->getConnection()->expects('insert')->with('insert into "child_table" ("attr", "val", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?, ?)',
            ['foo', 'bar', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

        $result = $model->children()->createOrFirst(['attr' => 'foo'], $values);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testCreateOrFirstMethodRetrievesExistingRecord(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->expects('getName')->returns('sqlite');

        $sql = 'insert into "child_table" ("attr", "val", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?, ?)';
        $bindings = ['foo', 'bar', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $model->getConnection()->expects('insert')->with($sql, $bindings)->throws(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], false, [])->returns([[
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]]);

        $result = $model->children()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodCreatesNewRecord(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite', [456]);
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->allows('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([]);

        $model->getConnection()->expects('insert')->with('insert into "child_table" ("attr", "val", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?, ?)',
            ['foo', 'bar', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

        $result = $model->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testCreateOrFirstMethodCreatesNewRecordWithoutValues(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite', [456]);
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->expects('getName')->returns('sqlite');

        $model->getConnection()->expects('insert')->with('insert into "child_table" ("attr", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

        $result = $model->children()->createOrFirst(['attr' => 'foo']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodCreatesNewRecordWithoutValues(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite', [456]);
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->allows('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([]);

        $model->getConnection()->expects('insert')->with('insert into "child_table" ("attr", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['foo', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

        $result = $model->children()->firstOrCreate(['attr' => 'foo']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesExistingRecord(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->expects('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([[
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ]]);

        $result = $model->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->allows('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([]);

        $sql = 'insert into "child_table" ("attr", "val", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?, ?)';
        $bindings = ['foo', 'bar', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $model->getConnection()->expects('insert')->with($sql, $bindings)->throws(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], false, [])->returns([[
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]]);

        $result = $model->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodCreatesNewRecord(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite', [456]);
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->allows('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([]);

        $model->getConnection()->expects('insert')->with('insert into "child_table" ("attr", "val", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?, ?)',
            ['foo', 'bar', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

        $result = $model->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'bar']);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodUpdatesExistingRecord(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->expects('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([[
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ]]);

        $model->getConnection()->expects('update')->with('update "child_table" set "val" = ?, "updated_at" = ? where "id" = ?',
            ['baz', '2023-01-01 00:00:00', 456])->returns(1);

        $result = $model->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testUpdateOrCreateMethodUpdatesRecordCreatedJustNow(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->allows('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([]);

        $sql = 'insert into "child_table" ("attr", "val", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?, ?)';
        $bindings = ['foo', 'baz', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'];

        $model->getConnection()->expects('insert')->with($sql, $bindings)->throws(new UniqueConstraintViolationException('sqlite', $sql, $bindings, new Exception()));

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], false, [])->returns([[
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]]);

        $model->getConnection()->expects('update')->with('update "child_table" set "val" = ?, "updated_at" = ? where "id" = ?',
            ['baz', '2023-01-01 00:00:00', 456])->returns(1);

        $result = $model->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    #[DataProvider('createOrFirstValues')]
    public function testUpdateOrCreateMethodAcceptsClosureValuesAndCreates(Closure|array $values): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite', [456]);
        $model->getConnection()->expects('transactionLevel')->returns(0);
        $model->getConnection()->allows('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([]);

        $model->getConnection()->expects('insert')->with('insert into "child_table" ("attr", "val", "parent_id", "updated_at", "created_at") values (?, ?, ?, ?, ?)',
            ['foo', 'bar', 123, '2023-01-01 00:00:00', '2023-01-01 00:00:00'])->returns(true);

        $result = $model->children()->updateOrCreate(['attr' => 'foo'], $values);
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame('bar', $result->val);
    }

    public function testUpdateOrCreateMethodAcceptsClosureValuesAndUpdates(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->expects('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([[
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ]]);

        $model->getConnection()->expects('update')->with('update "child_table" set "val" = ?, "updated_at" = ? where "id" = ?',
            ['baz', '2023-01-01 00:00:00', 456])->returns(1);

        $result = $model->children()->updateOrCreate(['attr' => 'foo'], fn () => ['val' => 'baz']);
        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame('baz', $result->val);
    }

    public function testFirstOrNewDoesNotInvokeClosureWhenRecordExists(): void
    {
        $model = new HasManyCreateOrFirstTestParentModel();
        $model->id = 123;
        $this->mockConnectionForModel($model, 'SQLite');
        $model->getConnection()->expects('getName')->returns('sqlite');

        $model->getConnection()->expects('select')->with('select * from "child_table" where "child_table"."parent_id" = ? and "child_table"."parent_id" is not null and ("attr" = ?) limit 1', [123, 'foo'], true, [])->returns([[
            'id' => 456,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]]);

        $callCount = 0;
        $result = $model->children()->firstOrNew(['attr' => 'foo'], function () use (&$callCount) {
            $callCount++;

            return ['val' => 'should-not-be-called'];
        });

        $this->assertSame(0, $callCount);
        $this->assertTrue($result->exists);
        $this->assertSame('bar', $result->val);
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
 */
class HasManyCreateOrFirstTestParentModel extends Model
{
    protected $table = 'parent_table';
    protected $guarded = [];

    public function children(): HasMany
    {
        return $this->hasMany(HasManyCreateOrFirstTestChildModel::class, 'parent_id');
    }
}

/**
 * @property int $id
 * @property int $parent_id
 */
class HasManyCreateOrFirstTestChildModel extends Model
{
    protected $table = 'child_table';
    protected $guarded = [];
}
