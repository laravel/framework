<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphLazyEagerLoadingTest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\Comment;
use Illuminate\Tests\App\Models\Relationships\MorphLazyEagerLoadPost as Post;
use Illuminate\Tests\App\Models\Relationships\MorphLazyEagerLoadUser as User;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphLazyEagerLoadingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $user = User::create();

        $post = tap((new Post)->user()->associate($user))->save();

        (new Comment)->commentable()->associate($post)->save();
    }

    public function testLazyEagerLoading()
    {
        $comment = Comment::first();

        $comment->loadMorph('commentable', [
            Post::class => ['user'],
        ]);

        $this->assertTrue($comment->relationLoaded('commentable'));
        $this->assertTrue($comment->commentable->relationLoaded('user'));
    }
}
