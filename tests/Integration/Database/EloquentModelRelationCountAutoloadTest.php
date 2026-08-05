<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRelationCountAutoloadTest;

use DB;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelRelationCountAutoloadTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->morphs('commentable');
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('likeable');
        });
    }

    protected function tearDown(): void
    {
        Model::automaticallyEagerLoadRelationships(false);
        Model::automaticallyEagerLoadRelationshipCounts(false);
        Model::automaticallyEagerLoadRelationshipExistence(false);
        Model::preventAccessingMissingAttributes(false);

        parent::tearDown();
    }

    public function testCountAutoloadForCollection()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        $post1 = Post::create();
        $post1->comments()->create();
        $post1->comments()->create();

        $post2 = Post::create();
        $post2->comments()->create();

        Post::create();

        $posts = Post::orderBy('id')->get();

        DB::enableQueryLog();

        $counts = [];

        foreach ($posts as $post) {
            $counts[] = $post->comments_count;
        }

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals([2, 1, 0], $counts);

        DB::disableQueryLog();
    }

    public function testCountAutoloadForSingleModel()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        $post = Post::create();
        $post->comments()->create();
        $post->comments()->create();

        DB::enableQueryLog();

        $post = Post::first();

        $this->assertEquals(2, $post->comments_count);
        $this->assertEquals(2, $post->comments_count);

        $this->assertCount(2, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadForNestedRelations()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        tap(Post::create(), function ($post) {
            tap($post->comments()->create(), function ($comment) {
                $comment->likes()->create();
                $comment->likes()->create();
            });

            $post->comments()->create();
        });

        tap(Post::create(), function ($post) {
            tap($post->comments()->create(), fn ($comment) => $comment->likes()->create());
        });

        $posts = Post::with(['comments' => fn ($query) => $query->orderBy('id')])->orderBy('id')->get();

        DB::enableQueryLog();

        $counts = [];

        foreach ($posts as $post) {
            foreach ($post->comments as $comment) {
                $counts[] = $comment->likes_count;
            }
        }

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals([2, 0, 1], $counts);

        DB::disableQueryLog();
    }

    public function testCountAutoloadForVariousNestedMorphRelations()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        tap(Post::create(), function ($post) {
            $post->likes()->create();
            $post->comments()->create();
        });

        tap(Video::create(), function ($video) {
            $video->comments()->create();
        });

        $comments = Comment::with('commentable')->orderBy('id')->get();

        DB::enableQueryLog();

        $counts = [];

        foreach ($comments as $comment) {
            $counts[] = $comment->commentable->likes_count;
        }

        // One aggregate query per morphed model type...
        $this->assertCount(2, DB::getQueryLog());
        $this->assertEquals([1, 0], $counts);

        DB::disableQueryLog();
    }

    public function testCountAutoloadResolvesCamelCaseRelationNames()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        $post = Post::create();
        $comment = $post->comments()->create();
        $post->comments()->create(['parent_id' => $comment->id]);

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertEquals(1, $posts[0]->comments_with_parent_count);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadWithSerialization()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        $post = Post::create();
        $post->comments()->create();

        DB::enableQueryLog();

        $post = unserialize(serialize($post));

        $this->assertEquals(1, $post->comments_count);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadIsSkippedWhenCountIsAlreadyLoaded()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::withCount('comments')->get();

        DB::enableQueryLog();

        $this->assertEquals(1, $posts[0]->comments_count);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadIsSkippedForNonRelationAttributes()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        Post::create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->missing_count);
        $this->assertNull($posts[0]->should_apply_status_count);
        $this->assertNull($posts[0]->_count);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadIsSkippedForModelsThatDoNotExist()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get()->push(new Post);

        DB::enableQueryLog();

        $this->assertNull($posts[1]->comments_count);
        $this->assertEquals(1, $posts[0]->comments_count);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountIsNotAutoloadedForModelsNotRetrievedFromAQuery()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        DB::enableQueryLog();

        $this->assertNull(Post::create()->comments_count);
        $this->assertNull((new Post)->comments_count);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountIsNotAutoloadedWhenDisabled()
    {
        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->comments_count);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountIsNotAutoloadedWhenOnlyRelationshipAutoloadingIsEnabled()
    {
        Model::automaticallyEagerLoadRelationships();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->comments_count);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountIsNotAutoloadedWhenOnlyExistsAutoloadingIsEnabled()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->comments_count);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadDoesNotEnableRelationshipAutoloading()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        tap(Post::create(), fn ($post) => $post->comments()->create());
        tap(Post::create(), fn ($post) => $post->comments()->create());

        $posts = Post::get();

        DB::enableQueryLog();

        foreach ($posts as $post) {
            $this->assertCount(1, $post->comments);
        }

        // Each relationship is lazy loaded on its own model rather than autoloaded...
        $this->assertCount(2, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadDoesNotLoadMissingRelationsWhenRelationshipAutoloadingIsDisabled()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        tap(Post::create(), function ($post) {
            tap($post->comments()->create(), fn ($comment) => $comment->likes()->create());
        });

        tap(Post::create(), function ($post) {
            tap($post->comments()->create(), fn ($comment) => $comment->likes()->create());
        });

        $posts = Post::get();

        DB::enableQueryLog();

        $comment = $posts[0]->comments->first();

        $this->assertEquals(1, $comment->likes_count);

        // One lazy load for the first post's comments, one aggregate for those comments...
        $this->assertCount(2, DB::getQueryLog());
        $this->assertFalse($posts[1]->relationLoaded('comments'));

        DB::disableQueryLog();
    }

    public function testCountAndExistsAutoloadingCanBeEnabledTogether()
    {
        Model::automaticallyEagerLoadRelationshipCounts();
        Model::automaticallyEagerLoadRelationshipExistence();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertEquals(1, $posts[0]->comments_count);
        $this->assertTrue($posts[0]->comments_exists);

        $this->assertCount(2, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testCountAutoloadWhenAccessingMissingAttributesIsPrevented()
    {
        Model::automaticallyEagerLoadRelationshipCounts();
        Model::preventAccessingMissingAttributes();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        $this->assertEquals(1, $posts[0]->comments_count);
    }

    public function testMissingAttributeExceptionIsStillThrownForNonRelationCounts()
    {
        Model::automaticallyEagerLoadRelationshipCounts();
        Model::preventAccessingMissingAttributes();

        Post::create();

        $posts = Post::get();

        $this->expectException(MissingAttributeException::class);

        $posts[0]->missing_count;
    }
}

class Comment extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public $timestamps = false;

    public function shouldApplyStatus()
    {
        return false;
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function commentsWithParent()
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNotNull('parent_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}

class Video extends Model
{
    public $timestamps = false;

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}

class Like extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
