<?php

namespace Illuminate\Tests\Integration\Database\EloquentCollectionLoadMissingTest;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentCollectionLoadMissingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('post_id');
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
        });

        User::create();

        Post::create(['user_id' => 1]);

        Comment::create(['parent_id' => null, 'post_id' => 1]);
        Comment::create(['parent_id' => 1, 'post_id' => 1]);
        Comment::create(['parent_id' => 2, 'post_id' => 1]);

        Revision::create(['comment_id' => 1]);
    }

    public function testLoadMissing()
    {
        $posts = Post::with('comments', 'user')->get();

        DB::enableQueryLog();

        $posts->loadMissing('comments.parent.revisions:revisions.comment_id', 'user:id');

        $this->assertCount(2, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertTrue($posts[0]->comments[1]->parent->relationLoaded('revisions'));
        $this->assertArrayNotHasKey('id', $posts[0]->comments[1]->parent->revisions[0]->getAttributes());
    }

    public function testLoadMissingWithClosure()
    {
        $posts = Post::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing(['comments.parent' => function ($query) {
            $query->select('id');
        }]);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertArrayNotHasKey('post_id', $posts[0]->comments[1]->parent->getAttributes());
    }

    public function testLoadMissingWithDuplicateRelationName()
    {
        $posts = Post::with('comments')->get();

        DB::enableQueryLog();

        $posts->loadMissing('comments.parent.parent');

        $this->assertCount(2, DB::getQueryLog());
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('parent'));
        $this->assertTrue($posts[0]->comments[1]->parent->relationLoaded('parent'));
    }
}

class Comment extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    public function revisions()
    {
        return $this->hasMany(Revision::class);
    }
}

class Post extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class Revision extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}

class User extends Model
{
    public $timestamps = false;
}
