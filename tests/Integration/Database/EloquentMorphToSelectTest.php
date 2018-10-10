<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphToSelectTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentMorphToSelectTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

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

    public function test_select()
    {
        $comments = Comment::with('commentable:id')->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function test_select_raw()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->selectRaw('id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function test_select_sub()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->selectSub(function ($query) {
                $query->select('id');
            }, 'id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function test_add_select()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->addSelect('id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function test_lazy_loading()
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
