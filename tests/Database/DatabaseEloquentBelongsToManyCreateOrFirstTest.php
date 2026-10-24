<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyCreateOrFirstTest extends TestCase
{
    protected function setUp(): void
    {
        Carbon::setTestNow('2023-01-01 00:00:00');

        $db = new DB;

        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $db->bootEloquent();
        $db->setAsGlobal();

        $pdo = $db->getConnection()->getPdo();

        $pdo->exec('create table "related_table" (
            "id" integer primary key autoincrement not null,
            "attr" varchar not null unique,
            "val" varchar null,
            "created_at" datetime null,
            "updated_at" datetime null
        )');
        $pdo->exec('create table "pivot_table" (
            "source_id" integer not null,
            "related_id" integer not null,
            unique ("source_id", "related_id")
        )');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        BelongsToManyCreateOrFirstTestSourceModel::unsetConnectionResolver();
    }

    #[DataProvider('createOrFirstValues')]
    public function testCreateOrFirstMethodCreatesNewRelated(Closure|array $values): void
    {
        $result = $this->related()->createOrFirst(['attr' => 'foo'], $values);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testCreateOrFirstMethodAssociatesExistingRelated(): void
    {
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->related()->createOrFirst(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->relatedRows());
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testFirstOrCreateMethodRetrievesExistingRelatedAlreadyAssociated(): void
    {
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);
        $this->seedPivot(123, 1);

        $result = $this->related()->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
            'pivot' => ['source_id' => 123, 'related_id' => 1],
        ], $result->toArray());
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testCreateOrFirstMethodRetrievesExistingRelatedAssociatedJustNow(): void
    {
        // The related record exists and is already attached, so neither the insert nor the attach can succeed...
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);
        $this->seedPivot(123, 1);

        $result = $this->related()->createOrFirst(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
            'pivot' => ['source_id' => 123, 'related_id' => 1],
        ], $result->toArray());
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testFirstOrCreateMethodRetrievesExistingRelatedAndAssociatesIt(): void
    {
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);

        $result = $this->related()->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame('bar', $result->val);
        $this->assertSame(1, $this->relatedRows());
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testFirstOrCreateMethodAssociatesRelatedOwnedByAnotherSource(): void
    {
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);
        $this->seedPivot(999, 1);

        $result = $this->related()->firstOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $result->id);
        $this->assertSame(1, $this->relatedRows());
        $this->assertSame([[999, 1], [123, 1]], $this->pivots());
    }

    public function testFirstOrCreateMethodFallsBackToCreateOrFirst(): void
    {
        $result = $this->related()->firstOrCreate(['attr' => 'foo'], ['val' => 'bar']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals([
            'id' => 1,
            'attr' => 'foo',
            'val' => 'bar',
            'created_at' => '2023-01-01T00:00:00.000000Z',
            'updated_at' => '2023-01-01T00:00:00.000000Z',
        ], $result->toArray());
        $this->assertSame(1, $this->relatedRows());
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testUpdateOrCreateMethodCreatesNewRelated(): void
    {
        $result = $this->related()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', DB::table('related_table')->value('val'));
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testUpdateOrCreateMethodUpdatesExistingRelated(): void
    {
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);
        $this->seedPivot(123, 1);

        $result = $this->related()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', DB::table('related_table')->value('val'));
        $this->assertSame(1, $this->relatedRows());
        $this->assertSame([[123, 1]], $this->pivots());
    }

    public function testUpdateOrCreateMethodUpdatesRelatedOwnedByAnotherSourceAndAssociatesIt(): void
    {
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);
        $this->seedPivot(999, 1);

        $result = $this->related()->updateOrCreate(['attr' => 'foo'], ['val' => 'baz']);

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame('baz', DB::table('related_table')->value('val'));
        $this->assertSame([[999, 1], [123, 1]], $this->pivots());
    }

    public function testUpdateOrCreateMethodAcceptsClosureValuesAndCreates(): void
    {
        $callCount = 0;

        $result = $this->related()->updateOrCreate(['attr' => 'foo'], function () use (&$callCount) {
            $callCount++;

            return ['val' => 'baz'];
        });

        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertSame(1, $callCount);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', DB::table('related_table')->value('val'));
    }

    public function testUpdateOrCreateMethodAcceptsClosureValuesAndUpdates(): void
    {
        $this->seedRelated(['attr' => 'foo', 'val' => 'bar']);
        $this->seedPivot(123, 1);

        $callCount = 0;

        $result = $this->related()->updateOrCreate(['attr' => 'foo'], function () use (&$callCount) {
            $callCount++;

            return ['val' => 'baz'];
        });

        $this->assertFalse($result->wasRecentlyCreated);
        $this->assertSame(1, $callCount);
        $this->assertSame('baz', $result->val);
        $this->assertSame('baz', DB::table('related_table')->value('val'));
    }

    public static function createOrFirstValues(): array
    {
        return [
            'array' => [['val' => 'bar']],
            'closure' => [fn () => ['val' => 'bar']],
        ];
    }

    protected function related(): BelongsToMany
    {
        $source = new BelongsToManyCreateOrFirstTestSourceModel;
        $source->id = 123;
        $source->exists = true;

        return $source->related();
    }

    protected function seedRelated(array $attributes): void
    {
        DB::table('related_table')->insert($attributes + [
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ]);
    }

    protected function seedPivot(int $source, int $related): void
    {
        DB::table('pivot_table')->insert(['source_id' => $source, 'related_id' => $related]);
    }

    /**
     * @return list<array{int, int}>
     */
    protected function pivots(): array
    {
        return DB::table('pivot_table')->orderBy('rowid')->get()
            ->map(fn ($row) => [(int) $row->source_id, (int) $row->related_id])
            ->all();
    }

    protected function relatedRows(): int
    {
        return DB::table('related_table')->count();
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
