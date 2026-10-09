<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyCreateOrFirstTest extends TestCase
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

        $schema = 'create table "child_table" (
            "id" integer primary key autoincrement not null,
            "parent_id" integer not null,
            "attr" varchar not null,
            "val" varchar null,
            "created_at" datetime null,
            "updated_at" datetime null,
            unique ("parent_id", "attr")
        )';

        $db->getConnection()->getPdo()->exec($schema);
        $db->getConnection('replicated')->getPdo()->exec($schema);
        $db->getConnection('replicated')->getReadPdo()->exec($schema);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        HasManyCreateOrFirstTestParentModel::unsetConnectionResolver();
        HasManyCreateOrFirstTestParentModel::unsetEventDispatcher();
    }

    #[DataProvider('createOrFirstValues')]
    public function testCreateOrFirstMethodCreatesNewRecord(Closure|array $values): void
    {
        $result = $this->children()->createOrFirst(['attr' => 'foo'], $values);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testCreateOrFirstMethodRetrievesExistingRecord(): void
    {
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar']);

        $result = $this->children()->createOrFirst(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateMethodCreatesNewRecord(): void
    {
        $result = $this->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testCreateOrFirstMethodCreatesNewRecordWithoutValues(): void
    {
        $result = $this->children()->createOrFirst(['attr' => 'foo']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'parent_id' => 123,
            'attr' => 'foo',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodCreatesNewRecordWithoutValues(): void
    {
        $result = $this->children()->firstOrCreate(['attr' => 'foo']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'parent_id' => 123,
            'attr' => 'foo',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
    }

    public function testFirstOrCreateMethodRetrievesExistingRecord(): void
    {
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar']);

        $result = $this->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        // The replica has not seen the record yet, so the insert collides and the writer is asked again...
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar'], 'replicated');

        $result = $this->children('replicated')->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->rows('replicated'));
    }

    public function testFirstOrCreateRetriesWithinTheParentWhenAnotherParentHasTheSameRecord(): void
    {
        $this->seed(['parent_id' => 999, 'attr' => 'foo', 'val' => 'other'], 'replicated');
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar'], 'replicated');

        $result = $this->children('replicated')->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(123, $result->parent_id);
        $this->assertSame('bar', $result->val);
    }

    public function testFirstOrCreateDoesNotTouchTheWriterWhenTheReplicaHasTheRecord(): void
    {
        $this->seedReplica(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar']);
        $sides = new \ArrayObject;
        DB::connection('replicated')->listen(fn (QueryExecuted $query) => $sides[] = $query->readWriteType);

        $result = $this->children('replicated')->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame('bar', $result->val);
        $this->assertNotContains('write', $sides->getArrayCopy());
    }

    public function testFirstOrCreateIgnoresRecordsOwnedByOtherParents(): void
    {
        $this->seed(['parent_id' => 999, 'attr' => 'foo', 'val' => 'other']);

        $result = $this->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame(123, $result->parent_id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(2, $this->rows());
    }

    public function testCreateOrFirstIgnoresRecordsOwnedByOtherParents(): void
    {
        $this->seed(['parent_id' => 999, 'attr' => 'foo', 'val' => 'other']);

        $result = $this->children()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame(123, $result->parent_id);
        $this->assertSame(2, $this->rows());
    }

    public function testUpdateOrCreateMethodCreatesNewRecord(): void
    {
        $result = $this->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'parent_id' => 123,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testUpdateOrCreateMethodUpdatesExistingRecord(): void
    {
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar']);

        $result = $this->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', $this->fresh()->val);
        $this->assertSame(1, $this->rows());
    }

    public function testUpdateOrCreateMethodUpdatesRecordCreatedJustNow(): void
    {
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar'], 'replicated');

        $result = $this->children('replicated')->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', $this->fresh('replicated', onWriter: true)->val);
        $this->assertSame(1, $this->rows('replicated'));
    }

    public function testUpdateOrCreateDoesNotUpdateRecordsOwnedByOtherParents(): void
    {
        $this->seed(['parent_id' => 999, 'attr' => 'foo', 'val' => 'other']);

        $result = $this->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame('other', DB::table('child_table')->where('parent_id', 999)->value('val'));
        $this->assertSame('bar', $this->fresh()->val);
    }

    #[DataProvider('createOrFirstValues')]
    public function testUpdateOrCreateMethodAcceptsClosureValuesAndCreates(Closure|array $values): void
    {
        $result = $this->children()->updateOrCreate(['attr' => 'foo'], $values);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame('bar', $result->val);
        $this->assertSame('bar', $this->fresh()->val);
    }

    public function testUpdateOrCreateMethodAcceptsClosureValuesAndUpdates(): void
    {
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar']);

        $result = $this->children()->updateOrCreate(['attr' => 'foo'], fn () => ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', $this->fresh()->val);
    }

    public function testFirstOrNewDoesNotInvokeClosureWhenRecordExists(): void
    {
        $this->seed(['parent_id' => 123, 'attr' => 'foo', 'val' => 'bar']);

        $callCount = 0;
        $result = $this->children()->firstOrNew(['attr' => 'foo'], function () use (&$callCount) {
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

    protected function children(string $connection = 'default'): HasMany
    {
        $parent = (new HasManyCreateOrFirstTestParentModel)->setConnection($connection);
        $parent->id = 123;

        return $parent->children();
    }

    protected function seed(array $attributes, string $connection = 'default'): void
    {
        DB::connection($connection)->table('child_table')->insert($attributes + [
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]);
    }

    protected function seedReplica(array $attributes): void
    {
        $attributes += ['created_at' => '2023-01-01 00:00:00', 'updated_at' => '2023-01-01 00:00:00'];

        DB::connection('replicated')->getReadPdo()->exec(sprintf(
            'insert into "child_table" (%s) values (%s)',
            implode(', ', array_map(fn ($column) => "\"{$column}\"", array_keys($attributes))),
            implode(', ', array_map(fn ($value) => "'{$value}'", $attributes))
        ));
    }

    protected function fresh(string $connection = 'default', bool $onWriter = false): HasManyCreateOrFirstTestChildModel
    {
        $query = $this->children($connection);

        return ($onWriter ? $query->useWritePdo() : $query)->where('attr', 'foo')->firstOrFail();
    }

    protected function rows(string $connection = 'default'): int
    {
        return DB::connection($connection)->table('child_table')->useWritePdo()->count();
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
