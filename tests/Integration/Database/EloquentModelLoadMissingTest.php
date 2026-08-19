<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelLoadMissingTest;

use DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\ModelLoadMissingComment as Comment;
use Illuminate\Tests\App\Models\Relationships\ModelLoadMissingPost as Post;
use Illuminate\Tests\App\Models\Relationships\ModelLoadMissingUser as User;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelLoadMissingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('comment_mentions_users', function (Blueprint $table) {
            $table->unsignedInteger('comment_id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('first_comment_id')->nullable();
            $table->string('content')->nullable();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('post_id');
            $table->string('content')->nullable();
        });

        Post::create();

        Comment::create(['parent_id' => null, 'post_id' => 1, 'content' => 'Hello <u:1> <u:2>']);
        Comment::create(['parent_id' => 1, 'post_id' => 1]);

        User::create(['name' => 'Taylor']);
        User::create(['name' => 'Otwell']);

        Comment::first()->mentionsUsers()->attach([1, 2]);

        Post::first()->update(['first_comment_id' => 1]);
    }

    public function testLoadMissing()
    {
        $post = Post::with('comments')->first();

        DB::enableQueryLog();

        $post->loadMissing('comments.parent');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertTrue($post->comments[0]->relationLoaded('parent'));
    }

    public function testLoadMissingNoUnnecessaryAttributeMutatorAccess()
    {
        $posts = Post::all();

        DB::enableQueryLog();

        $posts->loadMissing('firstComment.parent');

        $this->assertCount(1, DB::getQueryLog());
    }
}
