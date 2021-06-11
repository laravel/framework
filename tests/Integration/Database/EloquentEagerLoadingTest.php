<?php

namespace Illuminate\Tests\Integration\Database\EloquentEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentEagerLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->string('status')->default('active');
        });
    }

    public function testAliasedEagerLoading()
    {
        $post = Post::create();
        $post->comments()->createMany([
            ['status' => 'active'],
            ['status' => 'inactive'],
            ['status' => 'inactive'],
            ['status' => 'active'],
            ['status' => 'inactive'],
        ]);

        $model = Post::with(['comments as activeComments' => function ($query) {
            return $query->where('status', 'active');
        }])->with(['comments as inactiveComments' => function ($query) {
            return $query->where('status', 'inactive');
        }])->find($post->id);

        $this->assertFalse($model->relationLoaded('comments'));
        $this->assertTrue($model->relationLoaded('activeComments'));
        $this->assertTrue($model->relationLoaded('inactiveComments'));

        $this->assertCount(2, $model->activeComments);
        $this->assertCount(3, $model->inactiveComments);
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
}

class Comment extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
