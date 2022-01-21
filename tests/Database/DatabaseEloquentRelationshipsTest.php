<?php

namespace Illuminate\Tests\Database\EloquentRelationshipsTest;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentRelationshipsTest extends TestCase
{
    public function testStandardRelationships()
    {
        $post = new Post;

        $this->assertInstanceOf(HasOne::class, $post->attachment());
        $this->assertInstanceOf(BelongsTo::class, $post->author());
        $this->assertInstanceOf(HasMany::class, $post->comments());
        $this->assertInstanceOf(MorphOne::class, $post->owner());
        $this->assertInstanceOf(MorphMany::class, $post->likes());
        $this->assertInstanceOf(BelongsToMany::class, $post->viewers());
        $this->assertInstanceOf(HasManyThrough::class, $post->lovers());
        $this->assertInstanceOf(HasOneThrough::class, $post->contract());
        $this->assertInstanceOf(MorphToMany::class, $post->tags());
        $this->assertInstanceOf(MorphTo::class, $post->postable());
    }

    public function testOverriddenRelationships()
    {
        $post = new CustomPost;

        $this->assertInstanceOf(CustomHasOne::class, $post->attachment());
        $this->assertInstanceOf(CustomBelongsTo::class, $post->author());
        $this->assertInstanceOf(CustomHasMany::class, $post->comments());
        $this->assertInstanceOf(CustomMorphOne::class, $post->owner());
        $this->assertInstanceOf(CustomMorphMany::class, $post->likes());
        $this->assertInstanceOf(CustomBelongsToMany::class, $post->viewers());
        $this->assertInstanceOf(CustomHasManyThrough::class, $post->lovers());
        $this->assertInstanceOf(CustomHasOneThrough::class, $post->contract());
        $this->assertInstanceOf(CustomMorphToMany::class, $post->tags());
        $this->assertInstanceOf(CustomMorphTo::class, $post->postable());
    }

    public function testAlwaysUnsetBelongsToRelationWhenReceivedModelId()
    {
        // create users
        $user1 = (new FakeRelationship)->forceFill(['id' => 1]);
        $user2 = (new FakeRelationship)->forceFill(['id' => 2]);

        // sync user 1 using Model
        $post = new Post;
        $post->author()->associate($user1);
        $post->syncOriginal();

        // associate user 2 using Model
        $post->author()->associate($user2);
        $this->assertTrue($post->isDirty());
        $this->assertTrue($post->relationLoaded('author'));
        $this->assertSame($user2, $post->author);

        // associate user 1 using model ID
        $post->author()->associate($user1->id);
        $this->assertTrue($post->isClean());

        // we must unset relation even if attributes are clean
        $this->assertFalse($post->relationLoaded('author'));
    }
}

class FakeRelationship extends Model
{
    //
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

    public function likes()
    {
        return $this->morphMany(FakeRelationship::class, 'actionable');
    }

    public function owner()
    {
        return $this->morphOne(FakeRelationship::class, 'property');
    }

    public function viewers()
    {
        return $this->belongsToMany(FakeRelationship::class);
    }

    public function lovers()
    {
        return $this->hasManyThrough(FakeRelationship::class, FakeRelationship::class);
    }

    public function contract()
    {
        return $this->hasOneThrough(FakeRelationship::class, FakeRelationship::class);
    }

    public function tags()
    {
        return $this->morphToMany(FakeRelationship::class, 'taggable');
    }

    public function postable()
    {
        return $this->morphTo();
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

    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new CustomMorphOne($query, $parent, $type, $id, $localKey);
    }

    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new CustomMorphMany($query, $parent, $type, $id, $localKey);
    }

    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
        $parentKey, $relatedKey, $relationName = null
    ) {
        return new CustomBelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    protected function newHasManyThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey,
        $secondKey, $localKey, $secondLocalKey
    ) {
        return new CustomHasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function newHasOneThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey,
        $secondKey, $localKey, $secondLocalKey
    ) {
        return new CustomHasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function newMorphToMany(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
        $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $inverse = false)
    {
        return new CustomMorphToMany($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
            $relationName, $inverse);
    }

    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        return new CustomMorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }
}

class CustomHasOne extends HasOne
{
    //
}

class CustomBelongsTo extends BelongsTo
{
    //
}

class CustomHasMany extends HasMany
{
    //
}

class CustomMorphOne extends MorphOne
{
    //
}

class CustomMorphMany extends MorphMany
{
    //
}

class CustomBelongsToMany extends BelongsToMany
{
    //
}

class CustomHasManyThrough extends HasManyThrough
{
    //
}

class CustomHasOneThrough extends HasOneThrough
{
    //
}

class CustomMorphToMany extends MorphToMany
{
    //
}

class CustomMorphTo extends MorphTo
{
    //
}
