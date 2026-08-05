<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRelationExistsAutoloadTest;

use DB;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelRelationExistsAutoloadTest extends DatabaseTestCase
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

    public function testExistsAutoloadForCollection()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        $post1 = Post::create();
        $post1->comments()->create();
        $post1->comments()->create();

        Post::create();

        $posts = Post::orderBy('id')->get();

        DB::enableQueryLog();

        $exists = [];

        foreach ($posts as $post) {
            $exists[] = $post->comments_exists;
        }

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame([true, false], $exists);

        DB::disableQueryLog();
    }

    public function testExistsAutoloadForSingleModel()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        $post = Post::create();
        $post->comments()->create();

        DB::enableQueryLog();

        $post = Post::first();

        $this->assertTrue($post->comments_exists);
        $this->assertTrue($post->comments_exists);

        $this->assertCount(2, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsAutoloadForNestedRelations()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        tap(Post::create(), function ($post) {
            tap($post->comments()->create(), fn ($comment) => $comment->likes()->create());

            $post->comments()->create();
        });

        tap(Post::create(), function ($post) {
            $post->comments()->create();
        });

        $posts = Post::with(['comments' => fn ($query) => $query->orderBy('id')])->orderBy('id')->get();

        DB::enableQueryLog();

        $exists = [];

        foreach ($posts as $post) {
            foreach ($post->comments as $comment) {
                $exists[] = $comment->likes_exists;
            }
        }

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame([true, false, false], $exists);

        DB::disableQueryLog();
    }

    public function testExistsAutoloadForVariousNestedMorphRelations()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        tap(Post::create(), function ($post) {
            $post->likes()->create();
            $post->comments()->create();
        });

        tap(Video::create(), function ($video) {
            $video->comments()->create();
        });

        $comments = Comment::with('commentable')->orderBy('id')->get();

        DB::enableQueryLog();

        $exists = [];

        foreach ($comments as $comment) {
            $exists[] = $comment->commentable->likes_exists;
        }

        // One aggregate query per morphed model type...
        $this->assertCount(2, DB::getQueryLog());
        $this->assertSame([true, false], $exists);

        DB::disableQueryLog();
    }

    public function testExistsAutoloadResolvesCamelCaseRelationNames()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        $post = Post::create();
        $comment = $post->comments()->create();
        $post->comments()->create(['parent_id' => $comment->id]);

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertTrue($posts[0]->comments_with_parent_exists);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsAutoloadWithSerialization()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        $post = Post::create();
        $post->comments()->create();

        DB::enableQueryLog();

        $post = unserialize(serialize($post));

        $this->assertTrue($post->comments_exists);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsAutoloadIsSkippedWhenExistsIsAlreadyLoaded()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        Post::create();

        $posts = Post::withExists('comments')->get();

        DB::enableQueryLog();

        $this->assertFalse($posts[0]->comments_exists);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsAutoloadIsSkippedForNonRelationAttributes()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        Post::create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->missing_exists);
        $this->assertNull($posts[0]->should_apply_status_exists);
        $this->assertNull($posts[0]->_exists);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsAutoloadIsSkippedForModelsThatDoNotExist()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get()->push(new Post);

        DB::enableQueryLog();

        $this->assertNull($posts[1]->comments_exists);
        $this->assertTrue($posts[0]->comments_exists);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsIsNotAutoloadedForModelsNotRetrievedFromAQuery()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

        DB::enableQueryLog();

        $this->assertNull(Post::create()->comments_exists);
        $this->assertNull((new Post)->comments_exists);

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsIsNotAutoloadedWhenDisabled()
    {
        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->comments_exists);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsIsNotAutoloadedWhenOnlyRelationshipAutoloadingIsEnabled()
    {
        Model::automaticallyEagerLoadRelationships();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->comments_exists);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsIsNotAutoloadedWhenOnlyCountAutoloadingIsEnabled()
    {
        Model::automaticallyEagerLoadRelationshipCounts();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $this->assertNull($posts[0]->comments_exists);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function testExistsAutoloadDoesNotEnableRelationshipAutoloading()
    {
        Model::automaticallyEagerLoadRelationshipExistence();

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

    public function testExistsAutoloadWhenAccessingMissingAttributesIsPrevented()
    {
        Model::automaticallyEagerLoadRelationshipExistence();
        Model::preventAccessingMissingAttributes();

        $post = Post::create();
        $post->comments()->create();

        $posts = Post::get();

        $this->assertTrue($posts[0]->comments_exists);
    }

    public function testMissingAttributeExceptionIsStillThrownForNonRelationExists()
    {
        Model::automaticallyEagerLoadRelationshipExistence();
        Model::preventAccessingMissingAttributes();

        Post::create();

        $posts = Post::get();

        $this->expectException(MissingAttributeException::class);

        $posts[0]->missing_exists;
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
