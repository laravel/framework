<?php

namespace Illuminate\Tests\Integration\Database\EloquentWhereHasMorphTest;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentWhereHasMorphTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->softDeletes();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->nullableMorphs('commentable');
            $table->softDeletes();
        });

        $models = [];

        $models[] = Post::create(['title' => 'foo']);
        $models[] = Post::create(['title' => 'bar']);
        $models[] = Post::create(['title' => 'baz']);
        end($models)->delete();

        $models[] = Video::create(['title' => 'foo']);
        $models[] = Video::create(['title' => 'bar']);
        $models[] = Video::create(['title' => 'baz']);
        $models[] = null; // deleted
        $models[] = null; // deleted

        foreach ($models as $model) {
            (new Comment)->commentable()->associate($model)->save();
        }
    }

    public function testWhereHasMorph()
    {
        $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query) {
            $query->where('title', 'foo');
        })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }

    public function testWhereHasMorphWithMorphMap()
    {
        Relation::morphMap(['posts' => Post::class]);

        Comment::where('commentable_type', Post::class)->update(['commentable_type' => 'posts']);

        try {
            $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

            $this->assertEquals([1, 4], $comments->pluck('id')->all());
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function testWhereHasMorphWithWildcard()
    {
        // Test newModelQuery() without global scopes.
        Comment::where('commentable_type', Video::class)->delete();

        $comments = Comment::withTrashed()
            ->whereHasMorph('commentable', '*', function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }

    public function testWhereHasMorphWithWildcardAndMorphMap()
    {
        Relation::morphMap(['posts' => Post::class]);

        Comment::where('commentable_type', Post::class)->update(['commentable_type' => 'posts']);

        try {
            $comments = Comment::whereHasMorph('commentable', '*', function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

            $this->assertEquals([1, 4], $comments->pluck('id')->all());
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function testWhereHasMorphWithWildcardAndOnlyNullMorphTypes()
    {
        Comment::whereNotNull('commentable_type')->forceDelete();

        $comments = Comment::query()
            ->whereHasMorph('commentable', '*', function (Builder $query) {
                $query->where('title', 'foo');
            })
            ->orderBy('id')->get();

        $this->assertEmpty($comments->pluck('id')->all());
    }

    public function testWhereHasMorphWithRelationConstraint()
    {
        $comments = Comment::whereHasMorph('commentableWithConstraint', Video::class, function (Builder $query) {
            $query->where('title', 'like', 'ba%');
        })->orderBy('id')->get();

        $this->assertEquals([5], $comments->pluck('id')->all());
    }

    public function testWhereHasMorphWitDifferentConstraints()
    {
        $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query, $type) {
            if ($type === Post::class) {
                $query->where('title', 'foo');
            }

            if ($type === Video::class) {
                $query->where('title', 'bar');
            }
        })->orderBy('id')->get();

        $this->assertEquals([1, 5], $comments->pluck('id')->all());
    }

    public function testWhereHasMorphWithOwnerKey()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('slug')->nullable();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_commentable_type_commentable_id_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->string('commentable_id')->nullable()->change();
        });

        Post::where('id', 1)->update(['slug' => 'foo']);

        Comment::where('id', 1)->update(['commentable_id' => 'foo']);

        $comments = Comment::whereHasMorph('commentableWithOwnerKey', Post::class, function (Builder $query) {
            $query->where('title', 'foo');
        })->orderBy('id')->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function testHasMorph()
    {
        $comments = Comment::hasMorph('commentable', Post::class)->orderBy('id')->get();

        $this->assertEquals([1, 2], $comments->pluck('id')->all());
    }

    public function testOrHasMorph()
    {
        $comments = Comment::where('id', 1)->orHasMorph('commentable', Video::class)->orderBy('id')->get();

        $this->assertEquals([1, 4, 5, 6], $comments->pluck('id')->all());
    }

    public function testDoesntHaveMorph()
    {
        $comments = Comment::doesntHaveMorph('commentable', Post::class)->orderBy('id')->get();

        $this->assertEquals([3], $comments->pluck('id')->all());
    }

    public function testOrDoesntHaveMorph()
    {
        $comments = Comment::where('id', 1)->orDoesntHaveMorph('commentable', Post::class)->orderBy('id')->get();

        $this->assertEquals([1, 3], $comments->pluck('id')->all());
    }

    public function testOrWhereHasMorph()
    {
        $comments = Comment::where('id', 1)
            ->orWhereHasMorph('commentable', Video::class, function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }

    public function testOrWhereHasMorphWithWildcardAndOnlyNullMorphTypes()
    {
        Comment::whereNotNull('commentable_type')->forceDelete();

        $comments = Comment::where('id', 7)
            ->orWhereHasMorph('commentable', '*', function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

        $this->assertEquals([7], $comments->pluck('id')->all());
    }

    public function testWhereDoesntHaveMorph()
    {
        $comments = Comment::whereDoesntHaveMorph('commentable', Post::class, function (Builder $query) {
            $query->where('title', 'foo');
        })->orderBy('id')->get();

        $this->assertEquals([2, 3], $comments->pluck('id')->all());
    }

    public function testWhereDoesntHaveMorphWithWildcardAndOnlyNullMorphTypes()
    {
        Comment::whereNotNull('commentable_type')->forceDelete();

        $comments = Comment::whereDoesntHaveMorph('commentable', [], function (Builder $query) {
            $query->where('title', 'foo');
        })->orderBy('id')->get();

        $this->assertEquals([7, 8], $comments->pluck('id')->all());
    }

    public function testOrWhereDoesntHaveMorph()
    {
        $comments = Comment::where('id', 1)
            ->orWhereDoesntHaveMorph('commentable', Post::class, function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

        $this->assertEquals([1, 2, 3], $comments->pluck('id')->all());
    }

    public function testModelScopesAreAccessible()
    {
        $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query) {
            $query->someSharedModelScope();
        })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }
}

class Comment extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function commentableWithConstraint()
    {
        return $this->morphTo('commentable')->where('title', 'bar');
    }

    public function commentableWithOwnerKey()
    {
        return $this->morphTo('commentable', null, null, 'slug');
    }
}

class Post extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    public function scopeSomeSharedModelScope($query)
    {
        $query->where('title', '=', 'foo');
    }
}

class Video extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function scopeSomeSharedModelScope($query)
    {
        $query->where('title', '=', 'foo');
    }
}
