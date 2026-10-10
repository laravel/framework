<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyThroughCreateOrFirstTest extends TestCase
{
    protected function setUp(): void
    {
        Carbon::setTestNow('2023-01-01 00:00:00');

        $db = new DB;

        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $db->setEventDispatcher(new Dispatcher);
        $db->bootEloquent();
        $db->setAsGlobal();

        $pdo = $db->getConnection()->getPdo();

        $pdo->exec('create table "pivot" ("id" integer primary key autoincrement not null, "parent_id" integer not null)');
        $pdo->exec('create table "child" (
            "id" integer primary key autoincrement not null,
            "pivot_id" integer null,
            "attr" varchar not null unique,
            "val" varchar null,
            "created_at" datetime null,
            "updated_at" datetime null
        )');

        // The parent owns the child through this pivot row...
        DB::table('pivot')->insert(['id' => 456, 'parent_id' => 123]);
        DB::table('pivot')->insert(['id' => 457, 'parent_id' => 999]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        HasManyThroughCreateOrFirstTestChildModel::flushEventListeners();
        HasManyThroughCreateOrFirstTestParentModel::unsetConnectionResolver();
        HasManyThroughCreateOrFirstTestParentModel::unsetEventDispatcher();
    }

    #[DataProvider('createOrFirstValues')]
    public function testCreateOrFirstMethodCreatesNewRecord(Closure|array $values): void
    {
        $result = $this->children()->createOrFirst(['attr' => 'foo'], $values);

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
        $this->seed(['attr' => 'foo', 'val' => 'bar', 'pivot_id' => 456]);

        $result = $this->children()->createOrFirst(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'pivot_id' => 456,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
            'laravel_through_key' => 123,
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateMethodCreatesNewRecord(): void
    {
        $result = $this->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);

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

    public function testFirstOrCreateMethodRetrievesExistingRecord(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar', 'pivot_id' => 456]);

        $result = $this->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateMethodRetrievesRecordCreatedJustNow(): void
    {
        $this->competingInsert(['attr' => 'foo', 'val' => 'bar', 'pivot_id' => 456]);

        $result = $this->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame(123, $result->laravel_through_key);
        $this->assertSame(1, $this->rows());
    }

    public function testFirstOrCreateThrowsWhenAnotherParentsRecordHoldsTheUniqueValue(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar', 'pivot_id' => 457]);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->children()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);
    }

    public function testUpdateOrCreateMethodCreatesNewRecord(): void
    {
        $result = $this->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'baz',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->rows());
    }

    public function testUpdateOrCreateMethodUpdatesExistingRecord(): void
    {
        $this->seed(['attr' => 'foo', 'val' => 'bar', 'pivot_id' => 456]);

        $result = $this->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', DB::table('child')->where('id', 1)->value('val'));
        $this->assertSame(1, $this->rows());
    }

    public function testUpdateOrCreateMethodUpdatesRecordCreatedJustNow(): void
    {
        $this->competingInsert(['attr' => 'foo', 'val' => 'baz', 'pivot_id' => 456]);

        $result = $this->children()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('baz', DB::table('child')->where('id', 1)->value('val'));
        $this->assertSame(1, $this->rows());
    }

    public static function createOrFirstValues(): array
    {
        return [
            'array' => [['val' => 'bar']],
            'closure' => [fn () => ['val' => 'bar']],
        ];
    }

    protected function children(): HasManyThrough
    {
        $parent = new HasManyThroughCreateOrFirstTestParentModel;
        $parent->id = 123;
        $parent->exists = true;

        return $parent->children();
    }

    protected function seed(array $attributes): void
    {
        DB::table('child')->insert($attributes + [
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]);
    }

    /**
     * Have another process create the record right before this one is inserted.
     */
    protected function competingInsert(array $attributes): void
    {
        HasManyThroughCreateOrFirstTestChildModel::creating(function () use ($attributes) {
            HasManyThroughCreateOrFirstTestChildModel::flushEventListeners();

            $this->seed($attributes);
        });
    }

    protected function rows(): int
    {
        return DB::table('child')->count();
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
