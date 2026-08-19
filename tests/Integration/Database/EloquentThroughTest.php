<?php

namespace Illuminate\Tests\Integration\Database\EloquentThroughTest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\OtherCommentable;
use Illuminate\Tests\App\Models\Relationships\ThroughComment as Comment;
use Illuminate\Tests\App\Models\Relationships\ThroughLike as Like;
use Illuminate\Tests\App\Models\Relationships\ThroughPost as Post;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentThroughTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('public');
        });

        Schema::create('other_commentables', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
        });

        $post = tap(new Post(['public' => true]))->save();
        $comment = tap((new Comment)->commentable()->associate($post))->save();
        (new Like())->comment()->associate($comment)->save();
        (new Like())->comment()->associate($comment)->save();

        $otherCommentable = tap(new OtherCommentable())->save();
        $comment2 = tap((new Comment)->commentable()->associate($otherCommentable))->save();
        (new Like())->comment()->associate($comment2)->save();
    }

    public function test()
    {
        /** @var Post $post */
        $post = Post::first();
        $this->assertEquals(2, $post->commentLikes()->count());
    }
}
