<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphToSelectTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphToSelectTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $post = Post::create();
        (new Comment)->commentable()->associate($post)->save();
    }

    public function testSelect()
    {
        $comments = Comment::with('commentable:id')->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testSelectRaw()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->selectRaw('id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testSelectSub()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->selectSub(function ($query) {
                $query->select('id');
            }, 'id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testAddSelect()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->addSelect('id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testLazyLoading()
    {
        $comment = Comment::first();
        $post = $comment->commentable()->select('id')->first();

        $this->assertEquals(['id' => 1], $post->getAttributes());
    }
}

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    //
}
