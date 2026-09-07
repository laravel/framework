<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class EloquentRelationshipUpsertTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('upsert_parents', function ($table) {
            $table->id();
        });

        Schema::create('upsert_children', function ($table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->string('parent_type')->default('parent');
            $table->string('reference')->unique();
            $table->string('name')->default('original');
            $table->timestamps();
        });

        Relation::morphMap(['parent' => UpsertParent::class]);
    }

    #[DataProvider('relationshipUpserts')]
    public function testUpsertPreservesOwnership($relationship, $update)
    {
        $parent = UpsertParent::create();
        $otherParent = UpsertParent::create();
        $owned = $parent->$relationship()->create(['reference' => 'owned']);
        $other = $otherParent->$relationship()->create(['reference' => 'other']);
        $original = $other->fresh()->getRawOriginal();
        $this->travel(1)->seconds();
        $relation = $parent->$relationship();

        $relation->upsert([
            ['reference' => 'owned', 'name' => 'updated'],
            ['reference' => 'other', 'name' => 'updated'],
            ['reference' => 'new', 'name' => 'inserted'],
        ], 'reference', $update);

        $this->assertSame($original, $other->fresh()->getRawOriginal());
        $this->assertSame('updated', $owned->fresh()->name);
        $this->assertSame($parent->id, UpsertChild::where('reference', 'new')->first()->parent_id);
        $this->assertSame([], $relation->getQuery()->getQuery()->upsertConstraints);
    }

    public static function relationshipUpserts()
    {
        foreach (['children', 'child', 'morphChildren', 'morphChild'] as $relationship) {
            yield $relationship.' default updates' => [$relationship, null];
            yield $relationship.' named updates' => [$relationship, ['name']];
            yield $relationship.' assigned updates' => [$relationship, ['name' => 'updated']];
        }
    }

    public function testMorphUpsertPreservesOwnerType()
    {
        $parent = UpsertParent::create();
        $other = UpsertChild::create([
            'parent_id' => $parent->id, 'parent_type' => 'another-parent', 'reference' => 'other',
        ]);
        $original = $other->fresh()->getRawOriginal();
        $this->travel(1)->seconds();

        $parent->morphChildren()->upsert([
            ['reference' => 'other', 'name' => 'updated'],
        ], 'reference');

        $this->assertSame($original, $other->fresh()->getRawOriginal());
    }

    public function testOwnerColumnsCannotBeReassignedByTheUpdateList()
    {
        $parent = UpsertParent::create();
        $child = $parent->morphChildren()->create(['reference' => 'owned']);

        $parent->morphChildren()->upsert([
            ['reference' => 'owned', 'name' => 'updated'],
        ], 'reference', ['parent_id' => 999, 'parent_type' => 'another-parent', 'name']);

        $this->assertSame($parent->id, $child->fresh()->parent_id);
        $this->assertSame('parent', $child->fresh()->parent_type);
        $this->assertSame('updated', $child->fresh()->name);
    }

    public function testUpsertWithExpressions()
    {
        $parent = UpsertParent::create();
        $child = $parent->children()->create(['reference' => 'owned']);

        $parent->children()->upsert([['reference' => 'owned']], 'reference', [
            'name' => new Expression("'updated'"),
        ]);

        $this->assertSame('updated', $child->fresh()->name);
    }

    public function testUpsertWithOnlyOwnershipUpdates()
    {
        $parent = UpsertParent::create();
        $child = $parent->children()->create(['reference' => 'owned']);

        $relation = $parent->children();
        $relation->getRelated()->timestamps = false;
        $relation->upsert([['reference' => 'owned']], 'reference', ['parent_id']);

        $this->assertSame($parent->id, $child->fresh()->parent_id);
        $this->assertSame(0, $parent->children()->upsert([], 'reference'));
    }
}

class UpsertParent extends Model
{
    protected $table = 'upsert_parents';

    public $timestamps = false;

    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(UpsertChild::class, 'parent_id');
    }

    public function child()
    {
        return $this->hasOne(UpsertChild::class, 'parent_id');
    }

    public function morphChildren()
    {
        return $this->morphMany(UpsertChild::class, 'parent');
    }

    public function morphChild()
    {
        return $this->morphOne(UpsertChild::class, 'parent');
    }
}

class UpsertChild extends Model
{
    protected $table = 'upsert_children';

    protected $guarded = [];
}
