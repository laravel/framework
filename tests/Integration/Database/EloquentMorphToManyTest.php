<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphToManyTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentMorphToManyTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('commentables', function (Blueprint $table) {
            $table->foreignId('comment_id');
            $table->integer('commentable_id');
            $table->string('commentable_type');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
    }

    public function testItQueryWhereExists()
    {
        $post = Post::create(['title' => Str::random()]);

        $comment = Comment::create(['name' => Str::random()]);
        $comment2 = Comment::create(['name' => Str::random()]);
        $comment3 = Comment::create(['name' => Str::random()]);

        $post->comments()->sync([
            $comment->id,
            $comment2->id,
        ]);

        $comments = $post->comments()->whereExists()->pluck('id');

        $this->assertSame([$comment->getKey(), $comment2->getKey()], $comments->all());
    }

    public function testItQueryWhereNotExists()
    {
        $post = Post::create(['title' => Str::random()]);

        $comment = Comment::create(['name' => Str::random()]);
        $comment2 = Comment::create(['name' => Str::random()]);
        $comment3 = Comment::create(['name' => Str::random()]);

        $post->comments()->sync([
            $comment->id,
            $comment2->id,
        ]);

        $comments = $post->comments()->whereNotExists()->pluck('id');

        $this->assertSame([$comment3->getKey()], $comments->all());
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];
    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphToMany(Comment::class, 'commentable');
    }
}

class Comment extends Model
{
    public $table = 'comments';
    public $timestamps = true;
    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }
}
