<?php

namespace Illuminate\Tests\Integration\Database\EloquentRelationshipsTest;

use Orchestra\Testbench\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @group integration
 */
class EloquentRelationshipsTest extends TestCase
{
    /**
     * @test
     * @group f
     */
    public function standard_relationships()
    {
        $post = new Post;

        $this->assertInstanceOf(HasOne::class, $post->attachment());
        $this->assertInstanceOf(BelongsTo::class, $post->author());
        $this->assertInstanceOf(HasMany::class, $post->comments());
    }

    /**
     * @test
     * @group f
     */
    public function overridden_relationships()
    {
        $post = new CustomPost;

        $this->assertInstanceOf(CustomHasOne::class, $post->attachment());
        $this->assertInstanceOf(CustomBelongsTo::class, $post->author());
        $this->assertInstanceOf(CustomHasMany::class, $post->comments());
    }
}

class FakeRelationship extends Model
{
}

class Post extends Model
{
    public function attachment()
    {
        return $this->hasOne(FakeRelationship::class);
    }

    public function author()
    {
        return $this->belongsTo(FakeRelationship::class);
    }

    public function comments()
    {
        return $this->hasMany(FakeRelationship::class);
    }
}

class CustomPost extends Post
{
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new CustomBelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new CustomHasMany($query, $parent, $foreignKey, $localKey);
    }

    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new CustomHasOne($query, $parent, $foreignKey, $localKey);
    }
}

class CustomHasOne extends HasOne
{
}

class CustomBelongsTo extends BelongsTo
{
}

class CustomHasMany extends HasMany
{
}
