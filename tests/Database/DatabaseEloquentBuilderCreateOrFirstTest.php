<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBuilderCreateOrFirstTest extends TestCase
{
    protected function setUp(): void
    {
        Carbon::setTestNow('2023-01-01 00:00:00');

        $db = new DB;

        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);

        // Reads come from a replica that has not caught up with the writer yet...
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'read' => ['database' => ':memory:'],
            'write' => ['database' => ':memory:'],
        ], 'replicated');

        $db->setEventDispatcher(new Dispatcher);
        $db->bootEloquent();
        $db->setAsGlobal();

        $schema = 'create table "records" (
            "id" integer primary key autoincrement not null,
            "attr" varchar not null unique,
            "val" varchar null,
            "count" integer not null default 0,
            "created_at" datetime null,
            "updated_at" datetime null
        )';

        $db->getConnection()->getPdo()->exec($schema);
        $db->getConnection('replicated')->getPdo()->exec($schema);
        $db->getConnection('replicated')->getReadPdo()->exec($schema);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        EloquentBuilderCreateOrFirstTestModel::unsetConnectionResolver();
        EloquentBuilderCreateOrFirstTestModel::unsetEventDispatcher();
    }

    #[DataProvider('createOrFirstValues')]
    public function testCreateOrFirstMethodCreatesNewRecord(Closure|array $values): void
    {
        $result = $this->query()->createOrFirst(['attr' => 'foo'], $values);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testCreateOrFirstMethodRetrievesExistingRecord(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->query()->createOrFirst(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateMethodRetrievesExistingRecord(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->query()->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateMethodCreatesNewRecord(): void
    {
        $result = $this->query()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        // The replica has not seen the record yet, so the insert collides and the writer is asked again...
        $this->seedWriter(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->query('replicated')->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->rows('replicated'));
    }

    public function testFirstOrCreateDoesNotTouchTheWriterWhenTheReplicaHasTheRecord(): void
    {
        $this->seedReplica(['attr' => 'foo', 'val' => 'bar']);
        $queries = $this->recordQueries('replicated');

        $result = $this->query('replicated')->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame('bar', $result->val);
        $this->assertNotContains('write', array_column($queries->getArrayCopy(), 1));
    }

    public function testUpdateOrCreateLeavesOtherColumnsAndCreatedAtAlone(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar', 'count' => 5, 'created_at' => '2022-12-31 00:00:00', 'updated_at' => '2022-12-31 00:00:00']);

        $this->query()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $record = (array) DB::connection()->table('records')->first();
        $this->assertSame('baz', $record['val']);
        $this->assertEquals(5, $record['count']);
        $this->assertSame('2022-12-31 00:00:00', $record['created_at']);
        $this->assertSame('2023-01-01 00:00:00', $record['updated_at']);
    }

    public function testUpdateOrCreateMethodUpdatesExistingRecord(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->query()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', $this->fresh()->val);
        $this->assertSame(1, $this->rows());
    }

    public function testUpdateOrCreateMethodCreatesNewRecord(): void
    {
        $result = $this->query()->updateOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testUpdateOrCreateMethodUpdatesRecordCreatedJustNow(): void
    {
        $this->seedWriter(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->query('replicated')->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', $this->fresh('replicated', onWriter: true)->val);
        $this->assertSame(1, $this->rows('replicated'));
    }

    public function testIncrementOrCreateMethodIncrementsExistingRecord(): void
    {
        $this->seed(['attr' => 'foo', 'count' => 1]);

        $result = $this->query()->incrementOrCreate(['attr' => 'foo'], 'count');

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertEquals(2, $result->count);
        $this->assertEquals(2, $this->fresh()->count);
    }

    public function testIncrementOrCreateMethodCreatesNewRecord(): void
    {
        $result = $this->query()->incrementOrCreate(['attr' => 'foo']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'count' => 1,
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertEquals(1, $this->fresh()->count);
    }

    public function testIncrementOrCreateMethodIncrementParametersArePassed(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar', 'count' => 1]);

        $result = $this->query()->incrementOrCreate(['attr' => 'foo'], step: 2, extra: ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals(3, $result->count);
        $this->assertSame('baz', $result->val);
        $this->assertEquals(3, $this->fresh()->count);
        $this->assertSame('baz', $this->fresh()->val);
    }

    public function testIncrementOrCreateMethodExtraParametersArePassedWhenCreating(): void
    {
        $result = $this->query()->incrementOrCreate(['attr' => 'foo'], step: 2, extra: ['val' => 'baz']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame('baz', $result->val);
        $this->assertSame(1, $result->count);
        $this->assertEquals(1, $this->fresh()->count);
        $this->assertSame('baz', $this->fresh()->val);
    }

    public function testIncrementOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        $this->seedWriter(['attr' => 'foo', 'count' => 1]);

        $result = $this->query('replicated')->incrementOrCreate(['attr' => 'foo']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertEquals(2, $result->count);
        $this->assertEquals(2, $this->fresh('replicated', onWriter: true)->count);
    }

    #[DataProvider('createOrFirstValues')]
    public function testUpdateOrCreateMethodAcceptsClosureValuesAndCreates(Closure|array $values): void
    {
        $result = $this->query()->updateOrCreate(['attr' => 'foo'], $values);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame('foo', $result->attr);
        $this->assertSame('bar', $result->val);
        $this->assertSame('bar', $this->fresh()->val);
    }

    public function testUpdateOrCreateMethodAcceptsClosureValuesAndUpdates(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->query()->updateOrCreate(['attr' => 'foo'], fn () => ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', $this->fresh()->val);
    }

    public function testUpdateOrCreateInvokesClosureExactlyOnceWhenCreating(): void
    {
        $callCount = 0;

        $result = $this->query()->updateOrCreate(['attr' => 'foo'], function () use (&$callCount) {
            $callCount++;

            return ['val' => 'bar'];
        });

        $this->assertSame(1, $callCount);
        $this->assertSame('bar', $result->val);
    }

    public function testUpdateOrCreateInvokesClosureExactlyOnceWhenUpdating(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar']);

        $callCount = 0;

        $result = $this->query()->updateOrCreate(['attr' => 'foo'], function () use (&$callCount) {
            $callCount++;

            return ['val' => 'baz'];
        });

        $this->assertSame(1, $callCount);
        $this->assertSame('baz', $result->val);
    }

    #[DataProvider('createOrFirstValues')]
    public function testFirstOrNewMethodAcceptsClosureValuesAndInstantiates(Closure|array $values): void
    {
        $result = $this->query()->firstOrNew(['attr' => 'foo'], $values);

        $this->assertFalse($result->exists);
        $this->assertSame('foo', $result->attr);
        $this->assertSame('bar', $result->val);
        $this->assertSame(0, $this->rows());
    }

    public function testFirstOrNewDoesNotInvokeClosureWhenRecordExists(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar']);

        $callCount = 0;
        $result = $this->query()->firstOrNew(['attr' => 'foo'], function () use (&$callCount) {
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

    protected function query(string $connection = 'default')
    {
        return (new EloquentBuilderCreateOrFirstTestModel)->setConnection($connection)->newQuery();
    }

    protected function seed(array $attributes): void
    {
        $this->seedWriter($attributes, 'default');
    }

    protected function seedWriter(array $attributes, string $connection = 'replicated'): void
    {
        DB::connection($connection)->table('records')->insert($attributes + [
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]);
    }

    protected function seedReplica(array $attributes): void
    {
        DB::connection('replicated')->getReadPdo()->exec(sprintf(
            'insert into "records" ("attr", "val", "created_at", "updated_at") values (%s)',
            implode(', ', array_map(fn ($value) => "'{$value}'", [$attributes['attr'], $attributes['val'], '2023-01-01 00:00:00', '2023-01-01 00:00:00']))
        ));
    }

    /**
     * Record the verb and read/write side of each query the connection reports.
     */
    protected function recordQueries(string $connection): \ArrayObject
    {
        $queries = new \ArrayObject;

        DB::connection($connection)->listen(function (QueryExecuted $query) use ($queries) {
            $queries[] = [strtok($query->sql, ' '), $query->readWriteType];
        });

        return $queries;
    }

    protected function fresh(string $connection = 'default', bool $onWriter = false): EloquentBuilderCreateOrFirstTestModel
    {
        $query = $this->query($connection);

        return ($onWriter ? $query->useWritePdo() : $query)->where('attr', 'foo')->firstOrFail();
    }

    protected function rows(string $connection = 'default'): int
    {
        return DB::connection($connection)->table('records')->useWritePdo()->count();
    }
}

class EloquentBuilderCreateOrFirstTestModel extends Model
{
    protected $table = 'records';
    protected $guarded = [];
}
